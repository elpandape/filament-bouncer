<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Console\AssignCommand;
use ElPandaPe\FilamentBouncer\Console\PolicyCommand;
use ElPandaPe\FilamentBouncer\Console\ReconcileCommand;
use ElPandaPe\FilamentBouncer\Policies\AbilityRowPolicy;
use ElPandaPe\FilamentBouncer\Policies\RolePolicy;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
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
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'filament-bouncer');

        // The grid is a table of the package's own, because Filament's components cannot
        // express one: a cell has to be a single control walking three stances, and a row
        // has to keep its name while the columns scroll.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-bouncer');

        // Registered with Filament rather than linked from the view, so `filament:assets`
        // publishes it beside the panel's own and no build step is asked of the
        // application. The stylesheet reads from the panel's colour tokens and defines no
        // utility class of its own, so nothing depends on the consuming application's
        // Tailwind build.
        FilamentAsset::register([
            Css::make('filament-bouncer', __DIR__.'/../resources/css/filament-bouncer.css'),
        ], 'elpandape/filament-bouncer');

        Gate::policy(Models::classname(Role::class), RolePolicy::class);

        // And the abilities screen is governed the same way. Without a policy Filament
        // falls open, and the screen that names every ability in the panel is exactly
        // the one nobody unasked should be reading.
        Gate::policy(Models::classname(Ability::class), AbilityRowPolicy::class);

        // Nobody owns a role, and saying so out loud is what keeps this package working
        // in an application that runs Eloquent strictly.
        //
        // Bouncer asks about ownership on every check it answers, and with nothing told
        // to it, it guesses the column from the name of whoever is asking: for a user
        // asking about a role it reaches for `roles.user_id`. That column has never
        // existed. Left lax the read returns null and the guess merely fails; under
        // `Model::shouldBeStrict()` it throws, and the roles screen dies with a message
        // naming a column nobody ever wrote.
        //
        // An application wanting a different answer registers its own after this, from a
        // provider of its own, and wins.
        Bouncer::ownedVia(Models::classname(Role::class), static fn (): bool => false);

        // And nobody owns an ability either, for exactly the same reason: with the
        // abilities screen registered, Bouncer starts being asked about ownership of the
        // ability rows themselves and reaches for `abilities.user_id`. The screen dies
        // inside a Filament view with a message naming a column nobody ever wrote.
        Bouncer::ownedVia(Models::classname(Ability::class), static fn (): bool => false);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-bouncer.php' => config_path('filament-bouncer.php'),
            ], 'filament-bouncer-config');

            $this->publishes([
                __DIR__.'/../stubs/policy.stub' => base_path('stubs/filament-bouncer.policy.stub'),
            ], 'filament-bouncer-stubs');

            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/filament-bouncer'),
            ], 'filament-bouncer-translations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/filament-bouncer'),
            ], 'filament-bouncer-views');

            $this->commands([
                AssignCommand::class,
                PolicyCommand::class,
                ReconcileCommand::class,
            ]);
        }
    }
}
