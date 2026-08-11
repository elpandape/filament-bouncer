<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament;

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
     * Nothing yet: the screens are being rewritten, and this registers them again as each
     * one lands.
     */
    public function register(Panel $panel): void
    {
        $panel->resources([]);
    }

    public function boot(Panel $panel): void
    {
        app(PanelGuard::class)->check($panel);
    }
}
