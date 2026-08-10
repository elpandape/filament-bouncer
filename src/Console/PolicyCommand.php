<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Console;

use Filament\Resources\Resource as FilamentResource;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Writes the policy a resource needs before any of its abilities can exist.
 *
 * The generated methods are the declaration: the catalogue reads them back, so what an
 * administrator is offered for a model is exactly what its policy is prepared to answer.
 * That is why this writes a file with six methods in it rather than a base class with
 * twelve — a policy that answers questions the model has no business being asked puts
 * switches on the screen that decide nothing.
 */
final class PolicyCommand extends Command
{
    protected $signature = 'filament-bouncer:policy
        {model?* : The models to write a policy for; the panel supplies them when none is named}
        {--panel= : The panel whose resources are walked}
        {--force : Overwrite a policy that is already there}';

    protected $description = 'Write the policy a model needs before its abilities can exist';

    public function handle(PanelResolver $panels, Filesystem $files, Repository $config): int
    {
        $models = $this->models($panels);

        if ($models === []) {
            $this->components->info(__('filament-bouncer::console.policies.none'));

            return self::SUCCESS;
        }

        $written = 0;

        foreach ($models as $model) {
            $path = $this->path($model);

            if ($files->exists($path) && ! $this->option('force')) {
                $this->components->twoColumnDetail($this->relative($path), '<fg=yellow>'.__('filament-bouncer::console.policies.kept').'</>');

                continue;
            }

            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, $this->render($files, $model, $this->userModel($config)));

            $this->components->twoColumnDetail($this->relative($path), '<fg=green>'.__('filament-bouncer::console.policies.written').'</>');

            $written++;
        }

        if ($written > 0) {
            $this->components->info(__('filament-bouncer::console.policies.next'));
        }

        return self::SUCCESS;
    }

    /**
     * The models to write for: the ones named on the command line, or every one behind a
     * resource of the panel that has no policy yet.
     *
     * @return array<int, class-string<Model>>
     */
    private function models(PanelResolver $panels): array
    {
        /** @var array<int, string> $named */
        $named = $this->argument('model');

        if ($named !== []) {
            /** @var array<int, class-string<Model>> $named */
            return $named;
        }

        /** @var string|null $id */
        $id = $this->option('panel');

        $models = [];

        foreach ($panels->resolve($id)->getResources() as $resource) {
            /** @var class-string<FilamentResource> $resource */
            $model = $resource::getModel();

            if (Gate::getPolicyFor($model) === null) {
                $models[$model] = $model;
            }
        }

        return array_values($models);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $user
     */
    private function render(Filesystem $files, string $model, string $user): string
    {
        $namespace = $this->namespace();

        // The two class names collide whenever the policy being written is the one for
        // the user model itself, which is a perfectly ordinary thing to want.
        $imports = array_unique([
            'use '.$model.';',
            'use '.$user.';',
            'use ElPandaPe\FilamentBouncer\Policies\AbilityPolicy;',
        ]);

        sort($imports);

        return str_replace(
            ['{{ namespace }}', '{{ imports }}', '{{ class }}', '{{ modelClass }}', '{{ modelVariable }}', '{{ userClass }}'],
            [
                $namespace,
                implode(PHP_EOL, $imports),
                class_basename($model).'Policy',
                class_basename($model),
                Str::camel(class_basename($model)),
                class_basename($user),
            ],
            $files->get($this->stub($files)),
        );
    }

    /**
     * An application that keeps its own stub wins, because how a policy is laid out is
     * that application's house style and not this package's business.
     */
    private function stub(Filesystem $files): string
    {
        $published = base_path('stubs/filament-bouncer.policy.stub');

        return $files->exists($published) ? $published : __DIR__.'/../../stubs/policy.stub';
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function path(string $model): string
    {
        return app_path('Policies/'.class_basename($model).'Policy.php');
    }

    private function namespace(): string
    {
        return $this->laravel->getNamespace().'Policies';
    }

    /**
     * The class the application signs people in as, so the generated methods name it
     * rather than the bare Eloquent model everything descends from.
     *
     * @return class-string
     */
    private function userModel(Repository $config): string
    {
        /** @var string|null $guard */
        $guard = $config->get('auth.defaults.guard');

        /** @var string|null $provider */
        $provider = $config->get('auth.guards.'.$guard.'.provider');

        /** @var string|null $model */
        $model = $config->get('auth.providers.'.$provider.'.model');

        return is_string($model) && is_subclass_of($model, Model::class) ? $model : Model::class;
    }

    private function relative(string $path): string
    {
        return Str::after($path, base_path().DIRECTORY_SEPARATOR);
    }
}
