<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Policies;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

/**
 * Who may work the roles screen.
 *
 * The screen that hands out abilities is governed by an ability like everything else,
 * and by the same catalogue: because the resource is registered on the panel and its
 * model now has a policy, "manage roles" appears in the grid as an ordinary row. Nothing
 * about it is special-cased, and nobody is quietly exempt.
 *
 * Nobody reaches the screen until somebody grants them these abilities, which is what the
 * role named in `privileged_role` is for.
 *
 * There is deliberately no `deleteAny`: Filament authorises a bulk delete once for the whole
 * selection, and the two refusals keeping the privileged role and your own out of reach live
 * on the resource, so a bulk delete would walk past both.
 *
 * An application that wants its own answer registers a policy for the role model from a
 * provider of its own, which boots after this one and wins.
 */
final class RolePolicy extends BouncerPolicy
{
    public function viewAny(Model $user): bool
    {
        return $this->allows($user, 'viewAny', $this->model());
    }

    public function view(Model $user, Model $role): bool
    {
        return $this->allows($user, 'view', $role);
    }

    public function create(Model $user): bool
    {
        return $this->allows($user, 'create', $this->model());
    }

    public function update(Model $user, Model $role): bool
    {
        return $this->allows($user, 'update', $role);
    }

    public function delete(Model $user, Model $role): bool
    {
        return $this->allows($user, 'delete', $role);
    }

    /**
     * @return class-string<Model>
     */
    private function model(): string
    {
        /** @var class-string<Model> $model */
        $model = Models::classname(Role::class);

        return $model;
    }
}
