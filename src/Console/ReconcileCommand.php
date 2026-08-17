<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Console;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Events\CatalogReconciledEvent;
use ElPandaPe\FilamentBouncer\Filament\PanelGuard;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Support\Causer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;

final class ReconcileCommand extends Command
{
    protected $signature = 'filament-bouncer:reconcile
        {--panel= : The panel whose components declare the catalogue}
        {--prune : Delete the stored abilities the catalogue no longer declares}
        {--check : Report the differences without writing, and fail if there are any}';

    protected $description = "Reconcile a panel's ability catalogue with Bouncer's store";

    public function handle(CatalogRegistry $catalogs, PanelResolver $panels, AbilityStore $store, PrivilegedRole $privileged, PanelGuard $guard): int
    {
        /** @var string|null $id */
        $id = $this->option('panel');

        $panel = $panels->resolve($id);

        $declared = $this->declared($catalogs->get($panel));
        $stored = $store->catalogued();

        $missing = array_diff_key($declared, $stored);
        $extra = array_diff_key($stored, $declared);

        if ($this->option('check')) {
            return $this->report($missing, $extra, $privileged, $guard->openResources($panel));
        }

        // Before anything else, because this is the way back in and the rest of the run
        // is of no help to somebody who has been locked out.
        $privileged->restore();

        $store->create($missing);
        $this->components->info($this->say('created', count($missing)));

        if ($this->option('prune')) {
            $store->delete($extra);
            $this->components->info($this->say('deleted', count($extra)));
        } else {
            $this->keep($extra);
        }

        // Bouncer invalidates nothing of its own accord, so without this the very next
        // check in this process would still answer from the state before the write.
        Bouncer::refresh();

        event(new CatalogReconciledEvent(
            count($missing),
            $this->option('prune') ? count($extra) : 0,
            Causer::current(),
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, Ability>  $missing
     * @param  array<string, Model>  $extra
     * @param  array<int, string>  $open
     */
    private function report(array $missing, array $extra, PrivilegedRole $privileged, array $open): int
    {
        $this->list(__('filament-bouncer::console.missing'), array_map(static fn (Ability $ability): string => $ability->describe(), $missing));
        $this->list(__('filament-bouncer::console.extra'), array_map($this->describe(...), $extra));
        $this->list(__('filament-bouncer::console.open'), $open);

        if ($open !== []) {
            return self::FAILURE;
        }

        if ($privileged->needsRestoring()) {
            $this->components->warn(__('filament-bouncer::console.privileged', ['name' => (string) $privileged->name()]));

            return self::FAILURE;
        }

        if ($missing === [] && $extra === []) {
            $this->components->info(__('filament-bouncer::console.matches'));

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * @param  array<string, Model>  $extra
     */
    private function keep(array $extra): void
    {
        if ($extra === []) {
            return;
        }

        $this->components->warn($this->say('kept', count($extra)));
    }

    /**
     * @param  array<array-key, string>  $names
     */
    private function list(string $heading, array $names): void
    {
        if ($names === []) {
            return;
        }

        $this->components->twoColumnDetail($heading, (string) count($names));
        $this->components->bulletList(array_values($names));
    }

    private function describe(Model $ability): string
    {
        /** @var string $name */
        $name = $ability->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $ability->getAttribute('entity_type');

        return Ability::describeFor($name, $entityType);
    }

    /**
     * @return array<string, Ability>
     */
    private function declared(Catalog $catalog): array
    {
        $declared = [];

        foreach ($catalog->abilities() as $ability) {
            $declared[$ability->identity()] = $ability;
        }

        return $declared;
    }

    /**
     * The count decides which of the two forms is read, which is the only reason these
     * lines are written as a pair rather than as a sentence with a number dropped in.
     */
    private function say(string $key, int $count): string
    {
        return trans_choice('filament-bouncer::console.'.$key, $count, ['count' => $count]);
    }
}
