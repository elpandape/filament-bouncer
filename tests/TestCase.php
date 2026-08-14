<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use ElPandaPe\FilamentBouncer\FilamentBouncerServiceProvider;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Comment;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Tag;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Providers\TestPanelProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as ApplicationTestCase;
use Silber\Bouncer\BouncerServiceProvider;

/**
 * The boot every test file asks for with `pest()->extend(TestCase::class)`, which resolves the
 * file calling it — hence no `->in(...)` anywhere in `tests/Pest.php`.
 *
 * What follows are traps already paid for: read the comment before simplifying the line.
 */
abstract class TestCase extends ApplicationTestCase
{
    use RefreshDatabase;

    /** @var Application */
    protected $app;

    private ?string $environment = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environment = $this->app->environment();

        // The applications this package is built for run Eloquent strictly, and that
        // changes what Bouncer does: a guess at a column that is not there stops being a
        // null and becomes an exception. Without this the suite tests a laxer world than
        // the one the package ships into.
        Model::shouldBeStrict();

        // No request has been through the panel's middleware here, so nothing has told
        // Filament which panel it is serving. Without this every resource resolves
        // against no panel at all and its pages refuse to mount.
        Filament::setCurrentPanel('test');
        Filament::bootCurrentPanel();
    }

    /**
     * A test exercising the production branch leaves the environment changed, and testbench's
     * teardown then runs `migrate`: `ConfirmableTrait` asks for confirmation against a mocked
     * `OutputStyle` and kills the test with `BadMethodCallException`.
     */
    protected function tearDown(): void
    {
        if ($this->environment !== null) {
            $environment = $this->environment;

            $this->app->detectEnvironment(static fn (): string => $environment);
        }

        parent::tearDown();
    }

    /**
     * Filament has to register before Livewire: `SupportServiceProvider` binds `DataStore` with
     * an unshared `bind()` that overwrites Livewire's instance, so every
     * `store($component)->set(...)` is lost and the render dies on a null error bag.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentBouncerServiceProvider::class,
            ActionsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            QueryBuilderServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            BouncerServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    /** @param  Application  $app */
    protected function getEnvironmentSetUp($app): void
    {
        /** @var Repository $config */
        $config = $app['config'];

        $config->set('app.key', 'base64:AckfSECXIvnK5r28GVIWUAxmbBSjTsmF/0kOO7HH+Z8=');

        $config->set('app.locale', 'en');
        $config->set('app.fallback_locale', 'en');

        $config->set('cache.default', 'array');
        $config->set('session.driver', 'array');
        $config->set('queue.default', 'sync');

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $config->set('auth.defaults.guard', 'web');
        $config->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);

        // Bouncer reads the user model out of this path when it boots. Left at
        // testbench's default it points at a class that does not exist here, and the
        // first query through `Models::user()` dies resolving it.
        $config->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);

        // The fixture panel only carries resources, pages and widgets, so these two
        // keys are the only way the suite reaches the other two kinds of entity.
        $config->set('filament-bouncer.models', [Tag::class, Comment::class]);
        $config->set('filament-bouncer.custom', ['impersonate-users' => 'write']);

        // `app_path()` is left pointing at testbench's skeleton on purpose: moving it with
        // `useAppPath()` makes `Application::getNamespace()` throw `Unable to detect application
        // namespace`, which the Blade compiler trips over resolving `<x-filament::…>` tags.
    }

    /**
     * Bouncer's tables are raised by requiring its migration file and calling `up()` by hand.
     * There is no shortcut: Bouncer registers only `bouncer:clean`, and plain `migrate` does not
     * reach it either, since nothing calls `loadMigrationsFrom()` — the file only comes out
     * through `vendor:publish`, which here has nowhere to publish to.
     *
     * The hook is `defineDatabaseMigrationsAfterDatabaseRefreshed()` and not
     * `defineDatabaseMigrations()`: testbench calls the second *before* refreshing the database,
     * and with sqlite in memory the refresh raises an empty one over everything created there.
     * The symptom is a `no such table` halfway through the suite.
     *
     * The path is hard-wired, which breaks the day Bouncer renames the file; the smoke test on
     * the tables is what turns that into an immediate failure rather than a mystery.
     */
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed(): void
    {
        $this->loadLaravelMigrations();

        /** @var Migration $migration */
        $migration = require dirname(__DIR__).'/vendor/silber/bouncer/migrations/create_bouncer_tables.php';

        $migration->up(); // @phpstan-ignore method.notFound

        // Only the abilities that are about one record need a record to be about, and
        // this is the fixture model the suite hands to Bouncer for that.
        Schema::create('posts', static function (Blueprint $table): void {
            $table->id();
            // The record page names the record a narrowed rule reaches, and it asks the
            // panel's resource to title it — so the fixture needs something to be titled by.
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }
}
