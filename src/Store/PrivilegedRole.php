<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Events\PrivilegedRoleRestoredEvent;
use ElPandaPe\FilamentBouncer\Support\Causer;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * The role that holds everything, and the way back in.
 *
 * Handing out abilities is itself an ability, so a panel can be talked into a state
 * where nobody left is able to hand anything out. This role is the answer: the screen
 * refuses to edit it, and the reconcile command puts it back whatever became of it.
 */
final readonly class PrivilegedRole
{
    public function __construct(
        private Repository $config,
        private Bouncer $bouncer,
    ) {}

    public function name(): ?string
    {
        /** @var string|null $name */
        $name = $this->config->get('filament-bouncer.privileged_role');

        return blank($name) ? null : $name;
    }

    public function isNamed(string $name): bool
    {
        return $this->name() === $name;
    }

    /**
     * Whether the person at the keyboard may hand this role on.
     *
     * Only somebody who already holds it. That protects nothing absolutely — whoever works the
     * roles screen can compose a role holding everything instead — but it keeps the way back in
     * from being one careless click away. Asked of the role and not of its abilities, so the
     * answer does not change with what the catalogue declares today.
     */
    public function mayBeHandedOutBy(?Model $editor): bool
    {
        $name = $this->name();

        if ($name === null) {
            return true;
        }

        return $editor instanceof Model
            && method_exists($editor, 'isAn')
            && $editor->isAn($name) === true;
    }

    /**
     * Whether taking this role off that holder would leave nobody holding it.
     *
     * A way back in that nobody holds is not one, and the screen is the only place it can
     * be lost by accident: the command that hands it out is deliberate by definition.
     */
    public function isLastHolder(Model $holder): bool
    {
        $name = $this->name();

        if ($name === null) {
            return false;
        }

        $role = Models::role()->newQuery()->where('name', $name)->first();

        if (! $role instanceof Model) {
            return false;
        }

        $holders = Models::table('assigned_roles');

        return DB::table($holders)->where('role_id', $role->getKey())->count() === 1
            && DB::table($holders)
                ->where('role_id', $role->getKey())
                ->where('entity_id', $holder->getKey())
                ->where('entity_type', $holder->getMorphClass())
                ->exists();
    }

    /**
     * Whether the role is named but gone, or named but no longer holds the wildcard.
     */
    public function needsRestoring(): bool
    {
        $name = $this->name();

        if ($name === null) {
            return false;
        }

        $role = Models::role()->newQuery()->where('name', $name)->first();

        return ! $role instanceof Model || ! $this->bouncer->getClipboard()->check(
            $role,
            AbilityStore::WILDCARD,
            AbilityStore::WILDCARD,
        );
    }

    /**
     * Creates it if it is gone, and grants it the wildcard if it lost it.
     *
     * The wildcard is granted rather than every ability the catalogue happens to hold
     * today, because a resource added tomorrow has to be covered by this role without
     * anybody remembering to come back for it. That the wildcard also grants abilities
     * nobody ever declared is exactly what is wanted here, and nowhere else.
     */
    public function restore(): void
    {
        $name = $this->name();

        // Asked before writing, not after: without this the deploy command grants the
        // wildcard on every run, and pointing the configuration at an ordinary role hands
        // it everything with nothing said.
        if ($name === null || ! $this->needsRestoring()) {
            return;
        }

        $this->bouncer->allow($name)->everything();

        // Bouncer invalidates nothing of its own accord, so without this a listener that
        // asks the Gate about this very role would still answer from before the grant.
        $this->bouncer->refresh();

        event(new PrivilegedRoleRestoredEvent($name, Causer::current()));
    }
}
