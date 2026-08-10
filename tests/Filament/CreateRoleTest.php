<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\CommentResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\PostResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Activity;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Stats;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Tag;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Policies\OpenRolePolicy;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    $this->post = Subject::keyFor(Post::class);
});

test('the grid offers the abilities the person filling it in holds', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['create', Post::class]]);

    $state = gridState(livewire(CreateRole::class));

    expect($state[$this->post]['viewAny'])->toBe(Stance::Neutral->value)
        ->and($state[$this->post]['create'])->toBe(Stance::Neutral->value);
});

test('the grid offers everything the panel declares, held or not', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    expect(offeredCells(gridState(livewire(CreateRole::class))))
        ->toContain("{$this->post}.forceDelete")
        ->toContain("{$this->post}.delete");
});

test('creating a role grants exactly the cells that were ticked', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['create', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            'abilities' => [$this->post => [
                'viewAny' => Stance::Granted->value,
                'create' => Stance::Neutral->value,
            ]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, 'viewAny', Post::class))->toBeTrue()
        ->and(holds($role, 'create', Post::class))->toBeFalse();
});

test('the form drops a cell for something the panel does not declare', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->set("data.abilities.{$this->post}.inventedByHand", Stance::Granted->value)
        ->set('data.abilities.no-such-subject.viewAny', Stance::Granted->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, 'inventedByHand', Post::class))->toBeFalse()
        ->and(abilityCount($role))->toBe(0);
});

test('a role needs a name of its own', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('a name already taken is refused', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('somebody who holds nothing at all still hands out everything', function (): void {
    Gate::policy(Models::classname(Role::class), OpenRolePolicy::class);

    signIn();

    expect(offeredCells(gridState(livewire(CreateRole::class))))
        ->toContain("{$this->post}.viewAny")
        ->toContain("{$this->post}.forceDelete");
});

test('a subject that cannot be asked an action leaves that cell of its row empty', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['delete', Post::class], ['viewAny', Tag::class]]);

    $tag = Subject::keyFor(Tag::class);

    $component = livewire(CreateRole::class);

    expect(offeredCells(gridState($component)))
        ->toContain("{$this->post}.delete")
        ->toContain("{$tag}.viewAny")
        ->not->toContain("{$tag}.delete");
});

test('a page, a widget and a custom ability are offered as lists in tabs of their own', function (): void {
    $page = 'page:'.Subject::keyFor(Settings::class);
    $widget = 'widget:'.Subject::keyFor(Stats::class);

    grant(signInAsRoleManager(), [
        ['viewAny', Post::class],
        [$page, null],
        [$widget, null],
        ['impersonate-users', null],
    ]);

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            'abilities.'.Subject::keyFor(Settings::class).'.'.Ability::ACCESS_ACTION => Stance::Granted->value,
            'abilities.'.Subject::keyFor(Stats::class).'.'.Ability::ACCESS_ACTION => Stance::Forbidden->value,
            'abilities.impersonate-users.'.Ability::CUSTOM_ACTION => Stance::Granted->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, $page))->toBeTrue()
        ->and(holds($role, $widget))->toBeFalse()
        ->and(holds($role, 'impersonate-users'))->toBeTrue();
});

test('a panel that declares nothing says so instead of drawing an empty grid', function (): void {
    config()->set('filament-bouncer.models', []);
    config()->set('filament-bouncer.custom', []);
    config()->set('filament-bouncer.ignore', [
        PostResource::class,
        CommentResource::class,
        Settings::class,
        Stats::class,
        Activity::class,
        RoleResource::class,
        AbilityResource::class,
    ]);
    app(CatalogRegistry::class)->forget();

    Gate::policy(Models::classname(Role::class), OpenRolePolicy::class);
    signIn();

    livewire(CreateRole::class)->assertSee(__('filament-bouncer::roles.form.empty'));
});
