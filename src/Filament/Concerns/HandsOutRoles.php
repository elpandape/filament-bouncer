<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * The one question both screens that hand out a role have to ask.
 *
 * Written once because two copies of a rule about the way back in drift, and the drift is
 * invisible until somebody needs the rule.
 *
 * Everything but the privileged role is handed out by whoever may work the screen at all: the
 * policy answered that, and a second answer here could disagree with the first.
 */
trait HandsOutRoles
{
    private static function mayBeHandedOut(string $name): bool
    {
        $privileged = app(PrivilegedRole::class);

        if (! $privileged->isNamed($name)) {
            return true;
        }

        $editor = Filament::auth()->user();

        return $privileged->mayBeHandedOutBy($editor instanceof Model ? $editor : null);
    }
}
