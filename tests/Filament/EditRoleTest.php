<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
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

function gridOf(Model $role): AbilityGrid
{
    /** @var EditRole $page */
    $page = livewire(EditRole::class, ['record' => $role->getKey()])->instance();

    /** @var AbilityGrid $grid */
    $grid = $page->getSchemaComponent('form.'.RoleForm::ABILITIES);

    return $grid;
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

test('the form drops a cell for something the panel does not declare', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->set("data.abilities.{$this->post}.inventedByHand", Stance::Granted->value)
        ->call('save')
        ->assertHasNoFormErrors();

    expect(abilityCount($role))->toBe(0);
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

test('a cell reached by a broader rule draws the answer, not the dash', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = role();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    $broader = gridOf($role)->getBroader();
    $post = Subject::keyFor(Post::class);

    expect($broader[$post]['viewAny'] ?? null)->toBeTrue()
        ->and($broader[$post][Ability::MANAGE_ACTION] ?? null)->toBeTrue();
});

test('a cell nothing reaches draws the dash', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    expect(gridOf(role())->getBroader()[Subject::keyFor(Post::class)]['viewAny'] ?? null)->toBeFalse();
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
        ->assertSeeHtml('class="fb-cell"')
        ->assertSeeHtml(__('filament-bouncer::stances.granted'))
        ->assertSeeHtml(__('filament-bouncer::stances.forbidden'));
});

test('the grid offers a grant covering a whole model, and writes it as the wildcard', function (): void {
    grant(signInAsRoleManager(), [[Ability::MANAGE_NAME, Post::class]]);

    $role = role();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet(["abilities.{$this->post}.".Ability::MANAGE_ACTION => Stance::Neutral->value])
        ->fillForm(["abilities.{$this->post}.".Ability::MANAGE_ACTION => Stance::Granted->value])
        ->call('save')
        ->assertHasNoFormErrors();

    Bouncer::refresh();

    expect(Bouncer::getClipboard()->check($role, 'delete', Post::class))->toBeTrue()
        ->and(Bouncer::getClipboard()->check($role, 'anything-invented-later', Post::class))->toBeTrue();
});

test('the grid grows no column that no row in it could fill', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class], ['impersonate-users', null]]);

    livewire(EditRole::class, ['record' => role()->getKey()])
        ->assertSee(__('filament-bouncer::actions.viewAny'))
        ->assertDontSeeHtml('<span class="fb-col-name">'.Ability::CUSTOM_ACTION.'</span>');
});
