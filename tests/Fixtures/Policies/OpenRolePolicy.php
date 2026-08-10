<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Policies;

/**
 * An application's own answer for the roles screen, replacing the package's. It lets
 * somebody in who holds no Bouncer ability at all, which is the one way the grid is ever
 * seen empty.
 */
final class OpenRolePolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function create(): bool
    {
        return true;
    }
}
