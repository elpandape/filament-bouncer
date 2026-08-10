<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use Illuminate\Support\ServiceProvider;

final class FilamentBouncerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-bouncer.php', 'filament-bouncer');

        // Shared, because building a catalogue walks every component of the panel and
        // reflects over every policy behind them, and one request asks for it more
        // than once.
        $this->app->singleton(CatalogRegistry::class);

        $this->app->singleton(AbilityStore::class);
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
