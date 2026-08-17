<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A role was deleted, taking every assignment and every stance it carried with it.
 *
 * The holders are read before the delete: afterwards there is nothing left to read, since the
 * foreign key retracts them and Bouncer's own hook detaches the abilities.
 *
 * @param  Collection<int, Model>  $holders
 */
final readonly class RoleDeletedEvent
{
    /** @param Collection<int, Model> $holders */
    public function __construct(
        public string $role,
        public Collection $holders,
        public int $abilities,
        public ?Model $causer,
    ) {}
}
