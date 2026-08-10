<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * Reads who holds one ability into the panel, and writes back what changed.
 *
 * Every write goes through the roles store, one role at a time, so this side inherits the
 * same guarantees the roles screen has: both kinds of row are cleared before a new stance
 * is written, and the clipboard is refreshed afterwards. A second way of writing the same
 * table would have been a second set of rules to keep in step.
 *
 * Which of the store's two doors a write goes through is decided by the row. A plain one
 * is a catalogue entry and goes through the grid's own path, so the cell here and the
 * cell there stay the same row. A narrowed one — about a single record, or about what its
 * holder owns — has no cell on the grid at all, and is written as itself: matching it to
 * the catalogue by name would hand out the plain rule instead, which is a great deal more
 * than anybody asked for.
 */
trait FillsAbilityHolders
{
    abstract public function getRecord(): Model;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data[AbilityForm::HOLDERS] = $this->currentHolders();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var array<string, string> $wanted */
        $wanted = is_array($data[AbilityForm::HOLDERS] ?? null) ? $data[AbilityForm::HOLDERS] : [];

        unset($data[AbilityForm::HOLDERS]);

        $this->writeHolders($wanted);

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function currentHolders(): array
    {
        if ($this->narrowed()) {
            $abilities = app(RoleAbilities::class);
            $holders = [];

            foreach (Models::role()->newQuery()->get() as $role) {
                /** @var Model $role */
                $holders[$this->keyOf($role)] = $abilities->stanceOnRow($role, $this->getRecord())->value;
            }

            return $holders;
        }

        $found = $this->located();

        if ($found === null) {
            return [];
        }

        [$key, $action] = $found;
        $abilities = app(RoleAbilities::class);
        $holders = [];

        foreach (Models::role()->newQuery()->get() as $role) {
            /** @var Model $role */
            /** @var scalar $id */
            $id = $role->getKey();

            $holders[(string) $id] = $abilities->toFormState($role)[$key][$action] ?? Stance::Neutral->value;
        }

        return $holders;
    }

    /**
     * @param  array<string, string>  $wanted
     */
    private function writeHolders(array $wanted): void
    {
        $narrowed = $this->narrowed();
        $found = $narrowed ? null : $this->located();

        if (! $narrowed && $found === null) {
            return;
        }

        $abilities = app(RoleAbilities::class);

        // Walked from what the form sent rather than from the roles table, so a role
        // created while somebody had this screen open is left exactly as it was instead
        // of being cleared by a state that never knew about it.
        foreach ($wanted as $id => $stance) {
            $role = Models::role()->newQuery()->find($id);

            if (! $role instanceof Model) {
                continue;
            }

            if ($narrowed) {
                $abilities->saveRow($role, $this->getRecord(), Stance::tryFrom($stance) ?? Stance::Neutral);

                continue;
            }

            /** @var array{0: string, 1: string} $found */
            [$key, $action] = $found;

            $abilities->save($role, [$key => [$action => $stance]]);
        }
    }

    /**
     * Whether this row narrows an ability, and so is written as itself rather than
     * through the catalogue entry it would otherwise be mistaken for.
     */
    private function narrowed(): bool
    {
        return app(AbilityStore::class)->isRestricted($this->getRecord());
    }

    private function keyOf(Model $role): string
    {
        /** @var scalar $key */
        $key = $role->getKey();

        return (string) $key;
    }

    /**
     * Where this stored row sits in the narrowed catalogue, if it sits there at all.
     *
     * @return array{0: string, 1: string}|null
     */
    private function located(): ?array
    {
        /** @var string $name */
        $name = $this->getRecord()->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $this->getRecord()->getAttribute('entity_type');

        $identity = Ability::identityFor($name, $entityType);

        foreach (app(CatalogRegistry::class)->current()->subjects as $subject) {
            foreach ($subject->cells() as $action => $ability) {
                if ($ability->identity() === $identity) {
                    return [$subject->key, $action];
                }
            }
        }

        return null;
    }
}
