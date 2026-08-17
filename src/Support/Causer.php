<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Whoever is at the keyboard, or nobody.
 *
 * Asked through the panel like everywhere else in this package. A command has no panel to ask,
 * and Filament throws rather than answering null there, so the throw is what says «nobody» —
 * which is the truth an audit wants about a break-glass path. A broken guard is a different
 * failure and a real error, so it is left to surface rather than reported as nobody.
 */
final class Causer
{
    public static function current(): ?Model
    {
        try {
            $user = Filament::auth()->user();
        } catch (NoDefaultPanelSetException) {
            return null;
        }

        return $user instanceof Model ? $user : null;
    }
}
