<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Whoever is at the keyboard, or nobody.
 *
 * Asked through the panel like everywhere else in this package. A command has no panel to ask,
 * and Filament throws rather than answering null there, so the throw is what says «nobody» —
 * which is the truth an audit wants about a break-glass path.
 */
final class Causer
{
    public static function current(): ?Model
    {
        try {
            $user = Filament::auth()->user();
        } catch (Throwable) {
            return null;
        }

        return $user instanceof Model ? $user : null;
    }
}
