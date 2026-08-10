<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Console;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
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

    public function handle(CatalogRegistry $catalogs, PanelResolver $panels, AbilityStore $store, PrivilegedRole $privileged): int
    {
        /** @var string|null $id */
        $id = $this->option('panel');

        $declared = $this->declared($catalogs->get($panels->resolve($id)));
        $stored = $store->catalogued();

        $missing = array_diff_key($declared, $stored);
        $extra = array_diff_key($stored, $declared);

        if ($this->option('check')) {
            return $this->report($missing, $extra, $privileged);
        }

        // Before anything else, because this is the way back in and the rest of the run
        // is of no help to somebody who has been locked out.
        $privileged->restore();

        $store->create($missing);
        $this->components->info(sprintf('Created %d %s.', count($missing), $this->noun(count($missing))));

        if ($this->option('prune')) {
            $store->delete($extra);
            $this->components->info(sprintf('Deleted %d %s, and every grant that pointed at one.', count($extra), $this->noun(count($extra))));
        } else {
            $this->keep($extra);
        }

        // Bouncer invalidates nothing of its own accord, so without this the very next
        // check in this process would still answer from the state before the write.
        Bouncer::refresh();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, Ability>  $missing
     * @param  array<string, Model>  $extra
     */
    private function report(array $missing, array $extra, PrivilegedRole $privileged): int
    {
        $this->list('Missing from the store', array_map(static fn (Ability $ability): string => $ability->describe(), $missing));
        $this->list('Stored but no longer declared', array_map($this->describe(...), $extra));

        if ($privileged->needsRestoring()) {
            $this->components->warn(sprintf('The privileged role [%s] is missing, or no longer holds the wildcard.', (string) $privileged->name()));

            return self::FAILURE;
        }

        if ($missing === [] && $extra === []) {
            $this->components->info('The store matches the catalogue.');

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

        $this->components->warn(sprintf('Left %d %s in place that the catalogue no longer declares. Pass --prune to delete them.', count($extra), $this->noun(count($extra))));
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

    private function noun(int $count): string
    {
        return $count === 1 ? 'ability' : 'abilities';
    }
}
