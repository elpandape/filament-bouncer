<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament;

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
     * The roles screen joins the panel here, which is also what puts it on the catalogue
     * it draws: a resource whose model has a policy contributes its actions like any
     * other, so "who may work the roles screen" is an ordinary row and nobody is quietly
     * exempt.
     *
     * The abilities screen follows as it is rewritten.
     */
    public function register(Panel $panel): void
    {
        $panel->resources([
            RoleResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        app(PanelGuard::class)->check($panel);
    }
}
