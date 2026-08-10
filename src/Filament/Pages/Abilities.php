<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Pages;

use BackedEnum;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogTab;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesPage;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;
use UnitEnum;

/**
 * The other axis: not what a role can do, but who can do a thing.
 *
 * Read only, and it has to be. An ability is not a record somebody creates: it is a
 * method on a policy, a page, a widget or a name in configuration, and the reconciliation
 * is what puts it in the store. A screen offering to create one would be offering to
 * store a name no `can()` will ever ask about.
 *
 * What it does add is the question the roles screen cannot answer without being read
 * five times over: for one ability, who holds it — and, for each of them, whether
 * anybody actually granted it or it merely fell out of a broader rule.
 */
final class Abilities extends Page
{
    use AuthorizesPage;

    protected string $view = 'filament-bouncer::pages.abilities';

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        /** @var string|BackedEnum|Htmlable|null $icon */
        $icon = config('filament-bouncer.abilities.icon');

        return $icon;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        /** @var string|UnitEnum|null $group */
        $group = config('filament-bouncer.navigation.group');

        return $group;
    }

    public static function getNavigationSort(): ?int
    {
        /** @var int|null $sort */
        $sort = config('filament-bouncer.abilities.sort');

        return $sort;
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        /** @var string $slug */
        $slug = config('filament-bouncer.abilities.slug', 'security/abilities');

        return $slug;
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-bouncer::abilities.title');
    }

    public function getTitle(): string
    {
        return __('filament-bouncer::abilities.title');
    }

    /**
     * The roles this screen reports on, named, in the order the store keeps them.
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var string $name */
            $name = $role->getAttribute('name');

            $roles[] = $name;
        }

        return $roles;
    }

    /**
     * Every ability, grouped the way the roles screen groups them, with what each role
     * says about it and how it came to say it.
     *
     * @return array<string, array{label: string, abilities: array<int, array{title: string, subject: string|null, name: string, scope: string, holders: array<int, array{role: string, stance: string, how: string|null}>}>}>
     */
    public function getTabs(): array
    {
        $abilities = app(RoleAbilities::class);
        $roles = Models::role()->newQuery()->orderBy('name')->get()->all();
        $tabs = [];

        foreach (app(EditableCatalog::class)->current()->tabs() as $value => $subjects) {
            $tab = CatalogTab::from((string) $value);
            $rows = [];

            foreach ($subjects as $subject) {
                foreach ($subject->cells() as $ability) {
                    $rows[] = [
                        'title' => $ability->title,
                        'subject' => $tab->isGrid() ? $subject->label : null,
                        'name' => $ability->name,
                        'scope' => $ability->scope->value,
                        'holders' => $this->holders($abilities, $roles, $subject, $ability),
                    ];
                }
            }

            $tabs[$tab->value] = [
                'label' => __('filament-bouncer::roles.tabs.'.$tab->value),
                'abilities' => $rows,
            ];
        }

        return $tabs;
    }

    /**
     * @return array<string, string>
     */
    public function getWords(): array
    {
        return [
            'direct' => __('filament-bouncer::abilities.direct'),
            'broader' => __('filament-bouncer::abilities.broader'),
            'nobody' => __('filament-bouncer::abilities.nobody'),
            'ability' => __('filament-bouncer::abilities.ability'),
            'empty' => __('filament-bouncer::abilities.empty'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getStanceWords(): array
    {
        return app(Labels::class)->stances();
    }

    /**
     * What each role says about one ability.
     *
     * `how` is the whole point of the screen. A role that answers yes without a row of
     * its own naming the ability holds it through something broader — and that is the
     * thing nobody can see from the roles screen without opening every role in turn.
     *
     * @param  array<int, Model>  $roles
     * @return array<int, array{role: string, stance: string, how: string|null}>
     */
    private function holders(RoleAbilities $abilities, array $roles, Subject $subject, Ability $ability): array
    {
        $holders = [];

        foreach ($roles as $role) {
            /** @var string $name */
            $name = $role->getAttribute('name');

            $action = $ability->name === Ability::MANAGE_NAME ? Ability::MANAGE_ACTION : $ability->action;
            $direct = Stance::tryFrom($abilities->toFormState($role)[$subject->key][$action] ?? '') ?? Stance::Neutral;
            $holds = $abilities->holds($role, $ability);

            $holders[] = [
                'role' => $name,
                'stance' => $direct === Stance::Neutral && $holds ? Stance::Granted->value : $direct->value,
                'how' => match (true) {
                    $direct !== Stance::Neutral => 'direct',
                    $holds => 'broader',
                    default => null,
                },
            ];
        }

        return $holders;
    }
}
