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

    public function register(Panel $panel): void
    {
        $panel->resources([
            AbilityResource::class,
            RoleResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        app(PanelGuard::class)->check($panel);
    }
}
