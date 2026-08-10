<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
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
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['create', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet([
            "abilities.{$this->post}.viewAny" => Stance::Granted->value,
            "abilities.{$this->post}.create" => Stance::Neutral->value,
        ]);
});

test('saving grants what was ticked and takes back what was cleared', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['create', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->fillForm([
            'abilities' => [$this->post => [
                'viewAny' => Stance::Neutral->value,
                'create' => Stance::Granted->value,
            ]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(holds($role, 'create', Post::class))->toBeTrue();
});

test('a grant the editor cannot see survives a save that never mentions it', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class], ['forceDelete', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->fillForm(['abilities' => [$this->post => ['viewAny' => Stance::Neutral->value]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'forceDelete', Post::class))->toBeTrue();
});

test('the form drops a cell smuggled into the request', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->set("data.abilities.{$this->post}.forceDelete", Stance::Granted->value)
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'forceDelete', Post::class))->toBeFalse();
});

test('it refuses to open a role the editor holds themselves', function (): void {
    $user = signInAsRoleManager();
    grant($user, [['viewAny', Post::class]]);

    $role = role();
    Bouncer::assign('editor')->to($user);
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])->assertForbidden();

    expect(RoleResource::canEdit($role))->toBeFalse();
});

test('it refuses to open the role that holds everything', function (): void {
    config()->set('filament-bouncer.privileged_role', 'owner');

    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role('owner');

    livewire(EditRole::class, ['record' => $role->getKey()])->assertForbidden();

    expect(RoleResource::canEdit($role))->toBeFalse()
        ->and(RoleResource::canDelete($role))->toBeFalse();
});

test('an ordinary role stays open', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])->assertOk();

    expect(RoleResource::canEdit($role))->toBeTrue();
});

test('a cell can be set to forbid, and the denial is what the role then carries', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class]]);

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->fillForm(['abilities' => [$this->post => ['viewAny' => Stance::Forbidden->value]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(holds($role, 'viewAny', Post::class))->toBeFalse();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet(["abilities.{$this->post}.viewAny" => Stance::Forbidden->value]);
});

test('the edit screen of a role nobody may delete offers no way to', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertActionVisible(TestAction::make('delete'));
});

test('a cell says so when the role holds the ability through a rule nobody set here', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet(["abilities.{$this->post}.viewAny" => Stance::Neutral->value])
        ->assertSee(__('filament-bouncer::roles.form.inherited'));
});

test('a cell says so when a broader denial beats the grant made in it', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::forbid($role)->everything();
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet(["abilities.{$this->post}.viewAny" => Stance::Granted->value])
        ->assertSee(__('filament-bouncer::roles.form.overruled'));
});

test('a cell says so when the role holds rules about it that the grid cannot write', function (): void {
    grant(signInAsRoleManager(), [['delete', Post::class]]);

    $role = role();
    Bouncer::allow($role)->toOwn(Post::class)->to('delete');
    Bouncer::allow($role)->to('delete', Post::forceCreate([]));
    Bouncer::allow($role)->to('delete', Post::forceCreate([]));
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet(["abilities.{$this->post}.delete" => Stance::Neutral->value])
        ->assertSee(__('filament-bouncer::roles.form.restricted_owned'))
        ->assertSee(trans_choice('filament-bouncer::roles.form.restricted_records', 2, ['count' => 2]));
});

test('a cell is read as a mark, and the word survives as its accessible name', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    livewire(EditRole::class, ['record' => role()->getKey()])
        ->assertSeeHtml('aria-label="'.__('filament-bouncer::stances.granted').'"')
        ->assertSeeHtml('aria-label="'.__('filament-bouncer::stances.forbidden').'"');
});
