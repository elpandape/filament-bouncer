<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Console\PolicyCommand;
use ElPandaPe\FilamentBouncer\Console\ReconcileCommand;
use ElPandaPe\FilamentBouncer\Policies\RolePolicy;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

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
        // The screen that hands out abilities is governed by an ability like everything
        // else. Without this the roles resource has no policy, and Filament falls open:
        // anybody who reaches the panel at all could rewrite every role in it.
        //
        // An application registering its own policy for the role model does so from a
        // provider that boots after this one, and wins.
        Gate::policy(Models::classname(Role::class), RolePolicy::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-bouncer.php' => config_path('filament-bouncer.php'),
            ], 'filament-bouncer-config');

            $this->publishes([
                __DIR__.'/../stubs/policy.stub' => base_path('stubs/filament-bouncer.policy.stub'),
            ], 'filament-bouncer-stubs');

            $this->commands([
                PolicyCommand::class,
                ReconcileCommand::class,
            ]);
        }
    }
}
