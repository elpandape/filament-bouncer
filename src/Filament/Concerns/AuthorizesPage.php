<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\ComponentAccess;

/**
 * Makes a Filament page ask the catalogue who may reach it.
 *
 * Filament's own answer is yes to everybody signed into the panel, so a page that does
 * not override it is a hole. Using this trait is one of the two ways to satisfy the
 * boot guard; writing your own `canAccess()` is the other.
 */
trait AuthorizesPage
{
    public static function canAccess(): bool
    {
        return app(ComponentAccess::class)->allows(static::class);
    }
}
