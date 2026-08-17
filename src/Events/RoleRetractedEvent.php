<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Somebody had a role taken away by one of this package's screens.
 *
 * The role travels as its name because that is what every write path has in hand; resolving the
 * model would cost a query on every write for a listener that may not exist.
 */
final readonly class RoleRetractedEvent
{
    public function __construct(
        public Model $authority,
        public string $role,
        public ?Model $causer,
    ) {}
}
