<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
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

    livewire(CreateRole::class)
        ->assertFormFieldExists("abilities.{$this->post}.viewAny")
        ->assertFormFieldExists("abilities.{$this->post}.create")
        ->assertOk();
});

test('the grid withholds the abilities they do not', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->assertFormFieldDoesNotExist("abilities.{$this->post}.forceDelete")
        ->assertFormFieldDoesNotExist("abilities.{$this->post}.delete");
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

test('the form drops a cell smuggled into the request', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->set("data.abilities.{$this->post}.forceDelete", Stance::Granted->value)
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, 'forceDelete', Post::class))->toBeFalse()
        ->and($role->abilities()->count())->toBe(0);
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

test('somebody holding nothing is told so instead of shown an empty grid', function (): void {
    Gate::policy(Models::classname(Role::class), OpenRolePolicy::class);

    signIn();

    livewire(CreateRole::class)
        ->assertFormFieldDoesNotExist("abilities.{$this->post}.viewAny")
        ->assertSee('You hold no abilities of your own');
});

test('a subject that cannot be asked an action leaves that cell of its row empty', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['delete', Post::class], ['viewAny', Tag::class]]);

    $tag = Subject::keyFor(Tag::class);

    livewire(CreateRole::class)
        ->assertFormFieldExists("abilities.{$this->post}.delete")
        ->assertFormFieldExists("abilities.{$tag}.viewAny")
        ->assertFormFieldDoesNotExist("abilities.{$tag}.delete")
        ->assertSee('Withdraw');
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
