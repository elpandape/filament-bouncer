<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * The roles grid read from the other end: one ability, every role.
 *
 * It writes the same rows the roles screen writes — the cell here and the cell there are
 * the same row of the same table — which is the point. Deciding who may do a thing is a
 * question people ask in both directions, and answering it in one of them only means
 * opening every role in turn to answer it in the other.
 *
 * Nothing is withheld from whoever the policy let in. A row the panel no longer declares
 * still has no cells, because there is nothing to line them up against.
 */
final class AbilityHolders extends Field
{
    protected string $view = 'filament-bouncer::forms.ability-holders';

    private ?Ability $ability = null;

    private ?Subject $subject = null;

    private string $cell = '';

    /**
     * The stored row itself, kept only when it narrows an ability.
     */
    private ?Model $row = null;

    private bool $located = false;

    public function isOffered(): bool
    {
        $this->locate();

        return $this->row instanceof Model || $this->ability instanceof Ability;
    }

    /**
     * The line under the list, which is not the same sentence for both kinds of row.
     */
    public function getLegend(): string
    {
        $this->locate();

        return $this->row instanceof Model
            ? __('filament-bouncer::abilities.narrowed_legend')
            : __('filament-bouncer::abilities.broader');
    }

    /**
     * Every role, what it says about this ability, and how it came to say it.
     *
     * @return array<int, array{key: string, name: string, stance: string, how: string|null}>
     */
    public function getHolders(): array
    {
        $this->locate();

        if ($this->row instanceof Model) {
            return $this->narrowedHolders($this->row);
        }

        if (! $this->ability instanceof Ability || ! $this->subject instanceof Subject) {
            return [];
        }

        $abilities = app(RoleAbilities::class);
        $action = $this->cell;
        $rows = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var Model $role */
            /** @var string $name */
            $name = $role->getAttribute('name');

            $direct = Stance::tryFrom($abilities->toFormState($role)[$this->subject->key][$action] ?? '') ?? Stance::Neutral;
            $holds = $abilities->holds($role, $this->ability);

            $rows[] = [
                'key' => $this->keyOf($role),
                'name' => $name,
                'stance' => $direct === Stance::Neutral && $holds ? Stance::Granted->value : $direct->value,
                'how' => match (true) {
                    $direct !== Stance::Neutral => 'direct',
                    $holds => 'broader',
                    default => null,
                },
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public function getStanceWords(): array
    {
        return app(Labels::class)->stances();
    }

    /**
     * @return array<int, string>
     */
    public function getOrder(): array
    {
        return [Stance::Neutral->value, Stance::Granted->value, Stance::Forbidden->value];
    }

    /**
     * @return array<string, string>
     */
    public function getWords(): array
    {
        return [
            'direct' => __('filament-bouncer::abilities.direct'),
            'broader' => __('filament-bouncer::abilities.broader_short'),
            'nobody' => __('filament-bouncer::abilities.nobody'),
            'withheld' => __('filament-bouncer::abilities.withheld'),
        ];
    }

    /**
     * Every role, and what it says about one narrowed row.
     *
     * Read off the row itself rather than through the catalogue, because the catalogue
     * knows this ability only in its plain form: matching by name and model alone would
     * line "may update the posts they wrote" up against the cell for "may update posts",
     * report the wrong stance, and write the wrong rule when somebody changed it.
     *
     * There is no `broader` here for the same reason there is no cell on the grid: a role
     * holding a wildcard answers yes to the narrowed question too, but that is a fact
     * about the wildcard, and saying it against a rule this screen can actually clear
     * would invite somebody to clear it and watch nothing change.
     *
     * @return array<int, array{key: string, name: string, stance: string, how: string|null}>
     */
    private function narrowedHolders(Model $row): array
    {
        $abilities = app(RoleAbilities::class);
        $holders = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var Model $role */
            /** @var string $name */
            $name = $role->getAttribute('name');

            $stance = $abilities->stanceOnRow($role, $row);

            $holders[] = [
                'key' => $this->keyOf($role),
                'name' => $name,
                'stance' => $stance->value,
                'how' => $stance === Stance::Neutral ? null : 'direct',
            ];
        }

        return $holders;
    }

    /**
     * Locates what this screen is deciding about: a narrowed row, or a catalogue entry.
     *
     * A plain row the catalogue no longer declares is neither, and carries no cells:
     * there is nothing to line them up against, and the list says as much in its own
     * column.
     *
     * Resolved on first use rather than while the schema is being built: a component has
     * no container yet at that point, so it has no record to ask about either.
     */
    private function locate(): void
    {
        if ($this->located) {
            return;
        }

        $this->located = true;

        $record = $this->getRecord();

        if ($record instanceof Model && app(AbilityStore::class)->isRestricted($record)) {
            $this->row = $record;

            return;
        }

        // Composed in one step rather than guarded: a component with no record simply
        // matches nothing, which is the same answer as a row the catalogue dropped.
        $identity = $record instanceof Model ? Ability::identityFor($this->nameOf($record), $this->entityOf($record)) : '';

        foreach (app(CatalogRegistry::class)->current()->subjects as $subject) {
            foreach ($subject->cells() as $action => $ability) {
                if ($ability->identity() === $identity) {
                    $this->subject = $subject;
                    $this->ability = $ability;
                    $this->cell = $action;

                    return;
                }
            }
        }
    }

    private function keyOf(Model $role): string
    {
        /** @var scalar $key */
        $key = $role->getKey();

        return (string) $key;
    }

    private function nameOf(Model $record): string
    {
        /** @var string $name */
        $name = $record->getAttribute('name');

        return $name;
    }

    private function entityOf(Model $record): ?string
    {
        /** @var string|null $entityType */
        $entityType = $record->getAttribute('entity_type');

        return $entityType;
    }
}
