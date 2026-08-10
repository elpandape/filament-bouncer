<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\ComponentAccess;

/**
 * Makes a Filament widget ask the catalogue who may see it.
 *
 * A widget is not merely decoration: it puts figures on a dashboard that its reader may
 * have no business knowing. Filament shows one to everybody signed into the panel unless
 * told otherwise.
 */
trait AuthorizesWidget
{
    public static function canView(): bool
    {
        return app(ComponentAccess::class)->allows(static::class);
    }
}
