<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Resource as FilamentResource;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Walks a panel and writes down what it is able to ask about.
 */
final readonly class CatalogBuilder
{
    /**
     * Laravel's own hook on a policy, which answers before every ability and is
     * therefore not one of them.
     */
    private const string POLICY_HOOK = 'before';

    public function __construct(
        private Repository $config,
        private Labels $labels,
    ) {}

    public function build(Panel $panel): Catalog
    {
        $ignored = $this->ignored();
        $entities = [];

        foreach ($this->reject($panel->getResources(), $ignored) as $resource) {
            // Aliased on the way in because Pint lower-cases a type inside a docblock,
            // and `resource` on its own is one of PHP's own type keywords.
            /** @var class-string<FilamentResource> $resource */

            // Filament hands its labels back in lower case, because it capitalises them
            // at the point of display. A row heading and an ability title are both
            // points of display, so the capital has to be put back here.
            $entity = $this->modelEntity(
                $resource::getModel(),
                Str::ucfirst($resource::getPluralModelLabel()),
                EntityKind::Resource,
            );

            if ($entity instanceof Entity) {
                $entities[$entity->key] = $entity;
            }
        }

        foreach ($this->reject($this->configured('models'), $ignored) as $model) {
            /** @var class-string<Model> $model */
            $entity = $this->modelEntity(
                $model,
                Str::headline(class_basename($model)),
                EntityKind::Model,
            );

            if ($entity instanceof Entity) {
                $entities[$entity->key] ??= $entity;
            }
        }

        foreach ($this->reject($panel->getPages(), $ignored) as $page) {
            /** @var class-string<Page> $page */
            $key = Entity::keyFor($page);
            $label = $page::getNavigationLabel();

            $entities[$key] = new Entity($key, $label, EntityKind::Page, null, [
                Ability::ACCESS_ACTION => Ability::forPage($key, $this->title($label, Ability::ACCESS_ACTION)),
            ]);
        }

        foreach ($this->reject($this->widgetClasses($panel), $ignored) as $widget) {
            $key = Entity::keyFor($widget);
            $label = Str::headline(class_basename($widget));

            $entities[$key] = new Entity($key, $label, EntityKind::Widget, null, [
                Ability::ACCESS_ACTION => Ability::forWidget($key, $this->title($label, Ability::ACCESS_ACTION)),
            ]);
        }

        foreach ($this->customAbilities() as $name => $scope) {
            $key = Entity::keyFor($name);
            $label = Str::headline($name);

            $entities[$key] = new Entity($key, $label, EntityKind::Custom, null, [
                Ability::CUSTOM_ACTION => Ability::custom($name, $label, AbilityScope::from($scope)),
            ]);
        }

        uasort($entities, static fn (Entity $a, Entity $b): int => [$a->kind->order(), $a->label] <=> [$b->kind->order(), $b->label]);

        return new Catalog($entities, $this->actions($entities));
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function modelEntity(string $model, string $label, EntityKind $kind): ?Entity
    {
        $abilities = [];

        foreach ($this->policyActions($model) as $action) {
            $abilities[$action] = Ability::forModel(
                $model,
                $action,
                $this->title($label, $action),
                AbilityScope::for($action, $this->scopes()),
            );
        }

        if ($abilities === []) {
            return null;
        }

        return new Entity(Entity::keyFor($model), $label, $kind, $model, $abilities,
            Ability::manage($model, $this->title($label, Ability::MANAGE_ACTION)));
    }

    /**
     * The actions a model's policy is prepared to answer.
     *
     * A model with no policy contributes nothing on purpose. Its abilities would be
     * ones no code ever consults, and handing an administrator a switch that decides
     * nothing is worse than showing no switch at all.
     *
     * @param  class-string<Model>  $model
     * @return array<int, string>
     */
    private function policyActions(string $model): array
    {
        $policy = Gate::getPolicyFor($model);

        if (! is_object($policy)) {
            return [];
        }

        $actions = [];

        foreach (new ReflectionClass($policy)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }

            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            if ($method->getName() === self::POLICY_HOOK) {
                continue;
            }

            $actions[] = $method->getName();
        }

        sort($actions);

        return $actions;
    }

    /**
     * @param  array<string, Entity>  $entities
     * @return array<string, AbilityScope>
     */
    private function actions(array $entities): array
    {
        $actions = [];

        foreach ($entities as $entity) {
            foreach ($entity->abilities as $action => $ability) {
                $actions[$action] = $ability->scope;
            }
        }

        $scopes = $actions;

        uksort($actions, static fn (string $a, string $b): int => [$scopes[$a]->order(), $a] <=> [$scopes[$b]->order(), $b]);

        return $actions;
    }

    /**
     * @return array<int, class-string<Widget>>
     */
    private function widgetClasses(Panel $panel): array
    {
        return array_map(
            static fn (string|WidgetConfiguration $widget): string => $widget instanceof WidgetConfiguration ? $widget->widget : $widget,
            array_values($panel->getWidgets()),
        );
    }

    /**
     * What the ability is called where a human meets it, which is Bouncer's own title
     * column as well as the screen. It follows whichever locale was in force when the
     * reconciliation ran, since that is when the column is written.
     */
    private function title(string $label, string $action): string
    {
        return $label.': '.$this->labels->action($action);
    }

    /**
     * @param  array<array-key, string>  $classes
     * @param  array<int, string>  $ignored
     * @return array<int, string>
     */
    private function reject(array $classes, array $ignored): array
    {
        return array_values(array_diff($classes, $ignored));
    }

    /**
     * @return array<int, string>
     */
    private function ignored(): array
    {
        return $this->configured('ignore');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function scopes(): array
    {
        /** @var array<string, array<int, string>> $scopes */
        $scopes = $this->config->get('filament-bouncer.scopes', []);

        return $scopes;
    }

    /**
     * @return array<string, string>
     */
    private function customAbilities(): array
    {
        /** @var array<string, string> $custom */
        $custom = $this->config->get('filament-bouncer.custom', []);

        return $custom;
    }

    /**
     * @return array<int, string>
     */
    private function configured(string $key): array
    {
        /** @var array<int, string> $values */
        $values = $this->config->get('filament-bouncer.'.$key, []);

        return $values;
    }
}
