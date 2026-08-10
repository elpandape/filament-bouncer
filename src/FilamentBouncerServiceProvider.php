<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer;

use Illuminate\Support\ServiceProvider;

final class FilamentBouncerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-bouncer.php', 'filament-bouncer');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-bouncer.php' => config_path('filament-bouncer.php'),
            ], 'filament-bouncer-config');
        }
    }
}
