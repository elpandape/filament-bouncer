<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    $this->post = Subject::keyFor(Post::class);
});

function role(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

test('the grid arrives holding what the role was granted', function (): void {
    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet([
            "abilities.{$this->post}.viewAny" => true,
            "abilities.{$this->post}.create" => false,
        ]);
});

test('saving grants what was ticked and takes back what was cleared', function (): void {
    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->fillForm([
            'abilities' => [$this->post => ['viewAny' => false, 'create' => true]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(holds($role, 'create', Post::class))->toBeTrue();
});

test('a grant the editor cannot see survives a save that never mentions it', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class], ['forceDelete', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->fillForm(['abilities' => [$this->post => ['viewAny' => false]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'forceDelete', Post::class))->toBeTrue();
});

test('the form drops a cell smuggled into the request', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->set("data.abilities.{$this->post}.forceDelete", true)
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'forceDelete', Post::class))->toBeFalse();
});

test('it refuses to open a role the editor holds themselves', function (): void {
    $user = signIn();
    grant($user, [['viewAny', Post::class]]);

    $role = role();
    Bouncer::assign('editor')->to($user);
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])->assertForbidden();

    expect(RoleResource::canEdit($role))->toBeFalse();
});

test('it refuses to open the role that holds everything', function (): void {
    config()->set('filament-bouncer.privileged_role', 'owner');

    grant(signIn(), [['viewAny', Post::class]]);

    $role = role('owner');

    livewire(EditRole::class, ['record' => $role->getKey()])->assertForbidden();

    expect(RoleResource::canEdit($role))->toBeFalse()
        ->and(RoleResource::canDelete($role))->toBeFalse();
});

test('an ordinary role stays open', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])->assertOk();

    expect(RoleResource::canEdit($role))->toBeTrue();
});
