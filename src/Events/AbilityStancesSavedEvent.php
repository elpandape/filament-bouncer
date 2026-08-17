<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * A write of a role's stances finished, after the last of its cells was announced.
 *
 * It exists so a listener can treat one save as one fact. `AbilityStanceChangedEvent` fires once
 * per cell, which is the truth about what was written but not about what somebody did: marking ten
 * boxes and pressing save is one act. Only the store knows where that act ends, so guessing the
 * boundary from outside — a buffer flushed when the request happens to end — is guessing at
 * something this event simply states.
 *
 * It carries no detail of its own. A listener that wants the cells already heard them; this says
 * there are no more coming.
 *
 * It never fires over a save that changed nothing, and never before the cells it closes.
 */
final readonly class AbilityStancesSavedEvent
{
    public function __construct(
        public Model $authority,
        public int $changes,
        public ?Model $causer,
    ) {}
}
