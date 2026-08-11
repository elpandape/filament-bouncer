<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class FilamentBouncerPlugin implements Plugin
{
    public static function make(): self
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'filament-bouncer';
    }

    /**
     * The two screens join the panel here, which is also what puts them on the catalogue
     * they draw: a resource whose model has a policy contributes its actions like any
     * other, so "who may work the roles screen" and "who may narrow an ability" are
     * ordinary rows and nobody is quietly exempt.
     */
    public function register(Panel $panel): void
    {
        $panel->resources([
            RoleResource::class,
            AbilityResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        app(PanelGuard::class)->check($panel);
    }
}
