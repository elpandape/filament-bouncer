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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as ApplicationTestCase;
use Silber\Bouncer\BouncerServiceProvider;

/**
 * El arranque que comparte toda la suite. Cada archivo de test se lo pide con una sola
 * línea en su cabecera:
 *
 *     pest()->extend(TestCase::class);
 *
 * `pest()` resuelve el archivo que la llama, así que no hace falta ningún `->in(...)` en
 * `tests/Pest.php` — y de hecho no hay ninguno.
 *
 * Casi todo lo que hay aquí abajo son trampas ya pagadas: cada comentario explica un
 * síntoma que costó encontrar. Antes de simplificar cualquiera de ellas, léela.
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
    }

    /**
     * Un test que ejercita la rama de producción deja el entorno cambiado. El teardown de
     * testbench lanza entonces un comando `migrate`, y `ConfirmableTrait` pide confirmación
     * en producción contra un `OutputStyle` simulado, matando el test con
     * `BadMethodCallException`.
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
     * El orden importa: Filament tiene que registrarse antes que Livewire.
     * `SupportServiceProvider` liga `DataStore` con un `bind()` no compartido que pisa la
     * instancia de Livewire, así que cada `store($component)->set(...)` se pierde y el
     * render muere con una bolsa de errores nula.
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
        // keys are the only way the suite reaches the other two kinds of subject.
        $config->set('filament-bouncer.models', [Tag::class, Comment::class]);
        $config->set('filament-bouncer.custom', ['impersonate-users' => 'write']);

        // `app_path()` se deja a propósito apuntando al esqueleto de testbench. Moverlo con
        // `useAppPath()` hace que `Application::getNamespace()` lance `Unable to detect
        // application namespace`, con lo que tropieza el compilador de Blade al resolver las
        // etiquetas `<x-filament::…>`.
    }

    /**
     * Las tablas de Bouncer se levantan haciendo `require` de su archivo de migración y
     * llamando a `up()` a mano. No hay atajo, y conviene saber por qué antes de intentar uno:
     *
     * - **No existe un comando `bouncer:migrations`.** Bouncer v1.0.4 registra un único
     *   comando artisan, `bouncer:clean` (`src/Console/` tiene un solo archivo). Llamar a
     *   cualquier otro muere con `CommandNotFoundException`.
     * - **Y `migrate` a secas tampoco basta**, porque Bouncer no llama a `loadMigrationsFrom()`
     *   en ningún sitio: su migración solo sale por `vendor:publish --tag=bouncer.migrations`,
     *   que aquí no tiene dónde publicar.
     *
     * El hook es `defineDatabaseMigrationsAfterDatabaseRefreshed()` y **no**
     * `defineDatabaseMigrations()`: testbench llama al segundo *antes* de refrescar la base, y
     * con sqlite en memoria el refresco levanta una vacía y se lleva por delante todo lo creado
     * ahí. El síntoma es un `no such table` a mitad de la suite.
     *
     * La ruta al archivo queda cableada, que es el precio de esta vía: se rompe el día que
     * Bouncer lo renombre. El test de humo que comprueba que las tablas existen es lo que
     * convierte esa rotura en un fallo inmediato en vez de en un misterio.
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
            $table->timestamps();
        });
    }
}
