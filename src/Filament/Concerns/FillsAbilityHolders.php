<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * Reads who holds one ability into the panel, and writes back what changed.
 *
 * Every write goes through the roles store, one role at a time, so this side inherits
 * the same three guarantees the roles screen has: nobody hands out what they do not
 * hold, both kinds of row are cleared before a new stance is written, and the clipboard
 * is refreshed afterwards. A second way of writing the same table would have been a
 * second set of rules to keep in step.
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
        $found = $this->located();

        if ($found === null) {
            return;
        }

        [$key, $action] = $found;
        $abilities = app(RoleAbilities::class);

        // Walked from what the form sent rather than from the roles table, so a role
        // created while somebody had this screen open is left exactly as it was instead
        // of being cleared by a state that never knew about it.
        foreach ($wanted as $id => $stance) {
            $role = Models::role()->newQuery()->find($id);

            if (! $role instanceof Model) {
                continue;
            }

            $abilities->save($role, [$key => [$action => $stance]]);
        }
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

        foreach (app(EditableCatalog::class)->current()->subjects as $subject) {
            foreach ($subject->cells() as $action => $ability) {
                if ($ability->identity() === $identity) {
                    return [$subject->key, $action];
                }
            }
        }

        return null;
    }
}
