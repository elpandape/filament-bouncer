<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
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

        if ($name === null) {
            return;
        }

        $this->bouncer->allow($name)->everything();
    }
}
