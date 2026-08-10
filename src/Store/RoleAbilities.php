<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Contracts\Scope;
use Silber\Bouncer\Database\Models;

/**
 * What a role has been granted, and how the roles screen changes it.
 */
final readonly class RoleAbilities
{
    public function __construct(
        private Bouncer $bouncer,
        private EditableCatalog $editable,
    ) {}

    /**
     * The role's grants, shaped the way the form holds them.
     *
     * @return array<string, array<string, bool>>
     */
    public function toFormState(Model $role): array
    {
        $granted = $this->granted($role);
        $state = [];

        foreach ($this->editable->current()->subjects as $key => $subject) {
            foreach ($subject->abilities as $action => $ability) {
                $state[$key][$action] = isset($granted[$ability->identity()]);
            }
        }

        return $state;
    }

    /**
     * Bring the role's grants in line with what the form was saved holding.
     *
     * The incoming state is never walked. Everything is driven off the catalogue this
     * authority may decide about, so a cell smuggled into the request for an ability
     * they do not hold has nothing to match against and changes nothing — and a grant
     * they cannot see is never taken away either.
     *
     * @param  array<string, array<string, bool>>  $state
     */
    public function save(Model $role, array $state): void
    {
        $granted = $this->granted($role);

        foreach ($this->editable->current()->subjects as $key => $subject) {
            foreach ($subject->abilities as $action => $ability) {
                $wanted = (bool) ($state[$key][$action] ?? false);

                if ($wanted === isset($granted[$ability->identity()])) {
                    continue;
                }

                $wanted
                    ? $this->bouncer->allow($role)->to($ability->name, $ability->entityType)
                    : $this->bouncer->disallow($role)->to($ability->name, $ability->entityType);
            }
        }

        // Bouncer invalidates nothing of its own accord, so without this the screen
        // would repaint from the state it held before the save.
        $this->bouncer->refresh();
    }

    /**
     * The identities of the abilities granted straight to the role.
     *
     * The pivot is joined by hand rather than read through the role's own relation,
     * because the role model is whatever the application configured and nothing
     * promises the analyser that it carries Bouncer's traits.
     *
     * @return array<string, true>
     */
    private function granted(Model $role): array
    {
        $abilities = Models::table('abilities');
        $permissions = Models::table('permissions');

        $query = Models::ability()->newQuery()
            ->join($permissions, $permissions.'.ability_id', '=', $abilities.'.id')
            ->where($permissions.'.entity_id', $role->getKey())
            ->where($permissions.'.entity_type', $role->getMorphClass())
            ->where($permissions.'.forbidden', false);

        /** @var Scope $scope */
        $scope = Models::scope();
        $scope->applyToRelationQuery($query, $permissions);

        $rows = $query->get([
            $abilities.'.name as name',
            $abilities.'.entity_type as entity_type',
        ]);

        $granted = [];

        foreach ($rows as $row) {
            /** @var string $name */
            $name = $row->getAttribute('name');

            /** @var string|null $entityType */
            $entityType = $row->getAttribute('entity_type');

            $granted[Ability::identityFor($name, $entityType)] = true;
        }

        return $granted;
    }
}
