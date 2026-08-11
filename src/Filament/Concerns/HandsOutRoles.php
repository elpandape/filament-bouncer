<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * The one question both screens that hand out a role have to ask.
 *
 * The two ask it in different places — a checklist on the form that creates an account,
 * a pull-down on the tab of one that exists — and answer it in the same words, which is
 * why it is written once. Two copies of a rule about the way back in is one copy too
 * many: they drift, and the drift is invisible until somebody needs the rule.
 *
 * Everything but the privileged role is handed out by whoever may work the screen at
 * all; the policy has already answered that, and asking a second time here would be a
 * second answer able to disagree with the first.
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
