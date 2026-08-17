<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use ElPandaPe\FilamentBouncer\Store\Stance;
use Illuminate\Database\Eloquent\Model;

/**
 * What an authority says about one rule changed.
 *
 * One event rather than a granted one and a revoked one: this package keeps three stances and
 * forbidding is the one it has that others do not, so splitting would lose it.
 */
final readonly class AbilityStanceChangedEvent
{
    public function __construct(
        public Model $authority,
        public AbilityRef $ability,
        public Stance $from,
        public Stance $to,
        public ?Model $causer,
    ) {}
}
