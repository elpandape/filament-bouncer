<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Tag;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    $this->post = Subject::keyFor(Post::class);
});

test('the grid offers the abilities the person filling it in holds', function (): void {
    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    livewire(CreateRole::class)
        ->assertFormFieldExists("abilities.{$this->post}.viewAny")
        ->assertFormFieldExists("abilities.{$this->post}.create")
        ->assertOk();
});

test('the grid withholds the abilities they do not', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->assertFormFieldDoesNotExist("abilities.{$this->post}.forceDelete")
        ->assertFormFieldDoesNotExist("abilities.{$this->post}.delete");
});

test('creating a role grants exactly the cells that were ticked', function (): void {
    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            'abilities' => [$this->post => ['viewAny' => true, 'create' => false]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, 'viewAny', Post::class))->toBeTrue()
        ->and(holds($role, 'create', Post::class))->toBeFalse();
});

test('the form drops a cell smuggled into the request', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->set("data.abilities.{$this->post}.forceDelete", true)
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, 'forceDelete', Post::class))->toBeFalse()
        ->and($role->abilities()->count())->toBe(0);
});

test('a role needs a name of its own', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    livewire(CreateRole::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('a name already taken is refused', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('somebody holding nothing is told so instead of shown an empty grid', function (): void {
    signIn();

    livewire(CreateRole::class)
        ->assertFormFieldDoesNotExist("abilities.{$this->post}.viewAny")
        ->assertSee('You hold no abilities of your own');
});

test('a subject that cannot be asked an action leaves that cell of its row empty', function (): void {
    grant(signIn(), [['viewAny', Post::class], ['delete', Post::class], ['viewAny', Tag::class]]);

    $tag = Subject::keyFor(Tag::class);

    livewire(CreateRole::class)
        ->assertFormFieldExists("abilities.{$this->post}.delete")
        ->assertFormFieldExists("abilities.{$tag}.viewAny")
        ->assertFormFieldDoesNotExist("abilities.{$tag}.delete")
        ->assertSee('Withdraw');
});
