<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * The role that holds everything was created or handed the wildcard back.
 *
 * It is the largest grant this package can make, and it happens from a deploy command where
 * nobody is signed in.
 */
final readonly class PrivilegedRoleRestoredEvent
{
    public function __construct(
        public string $role,
        public ?Model $causer,
    ) {}
}
