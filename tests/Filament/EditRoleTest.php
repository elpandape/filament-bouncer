<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Tag;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function editedRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function editedCatalogCells(): int
{
    return collect(app(CatalogRegistry::class)->current()->subjects)
        ->sum(static fn (Subject $subject): int => count($subject->cells()));
}

/**
 * @param  array<string, array<string, string>>  $stances
 */
function editedWith(Model $role, array $stances): void
{
    livewire(EditRole::class, ['record' => $role->getKey()])
        ->set('data.'.RoleForm::ABILITIES, $stances)
        ->call('save')
        ->assertHasNoFormErrors();
}

test('the screen arrives holding what the role was granted', function (): void {
    signInAsRoleManager();

    $role = editedRole();
    grant($role, [['viewAny', Post::class]]);

    /** @var array<string, array<string, string>> $state */
    $state = livewire(EditRole::class, ['record' => $role->getKey()])->get('data.'.RoleForm::ABILITIES);

    expect($state[Subject::keyFor(Post::class)]['viewAny'] ?? null)->toBe(Stance::Granted->value)
        ->and($state[Subject::keyFor(Post::class)]['delete'] ?? null)->toBe(Stance::Neutral->value);
});

test('saving grants what is ticked and takes away what is not', function (): void {
    signInAsRoleManager();

    $role = editedRole();
    grant($role, [['viewAny', Post::class]]);

    editedWith($role, [
        Subject::keyFor(Post::class) => [
            'viewAny' => Stance::Neutral->value,
            'create' => Stance::Granted->value,
        ],
    ]);

    expect(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(holds($role, 'create', Post::class))->toBeTrue();
});

test('a grant nobody was asked about survives a save that never mentions it', function (): void {
    signInAsRoleManager();

    $role = editedRole();
    grant($role, [['viewAny', Post::class]]);

    editedWith($role, [Subject::keyFor(Tag::class) => ['viewAny' => Stance::Granted->value]]);

    expect(holds($role, 'viewAny', Post::class))->toBeTrue()
        ->and(holds($role, 'viewAny', Tag::class))->toBeTrue();
});

test('a cell the panel does not declare changes nothing', function (): void {
    signInAsRoleManager();

    $role = editedRole();

    editedWith($role, ['made-up-subject' => ['made-up-action' => Stance::Granted->value]]);

    expect(abilityCount($role))->toBe(0);
});

test('a role the editor holds refuses to open', function (): void {
    $editor = signInAsRoleManager();

    $role = editedRole();
    Bouncer::assign('editor')->to($editor);
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])->assertForbidden();
});

test('the way back in refuses to open, whoever is asking', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    livewire(EditRole::class, ['record' => editedRole('super-admin')->getKey()])->assertForbidden();
});

test('an ordinary role opens', function (): void {
    signInAsRoleManager();

    livewire(EditRole::class, ['record' => editedRole()->getKey()])
        ->assertSeeHtml('class="fb-table"')
        ->assertOk();
});

test('a cell may forbid, and forbidding is what the role then carries', function (): void {
    signInAsRoleManager();

    $role = editedRole();

    editedWith($role, [Subject::keyFor(Post::class) => ['delete' => Stance::Forbidden->value]]);

    Bouncer::allow($role)->to('delete', Post::class);
    Bouncer::refresh();

    expect(holds($role, 'delete', Post::class))->toBeFalse();
});

test('a grant covering a whole model is written as the wildcard', function (): void {
    signInAsRoleManager();

    $role = editedRole();

    editedWith($role, [Subject::keyFor(Post::class) => [Ability::MANAGE_ACTION => Stance::Granted->value]]);

    expect(holds($role, Ability::MANAGE_NAME, Post::class))->toBeTrue()
        ->and(holds($role, 'forceDelete', Post::class))->toBeTrue();
});

test('a row says when the role reaches it through a rule of its own it does not hold', function (): void {
    signInAsRoleManager();

    $role = editedRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertSee(__('filament-bouncer::roles.form.inherited'));
});

test('a row says when a broader denial beats the grant made on it', function (): void {
    signInAsRoleManager();

    $role = editedRole();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::forbid($role)->to(Ability::MANAGE_NAME, Post::class);
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertSee(__('filament-bouncer::roles.form.overruled'));
});

test('a row says when the role holds rules the grid cannot write', function (): void {
    signInAsRoleManager();

    $role = editedRole();
    $post = Post::query()->create();

    Bouncer::allow($role)->toOwn(Post::class)->to('delete');
    Bouncer::allow($role)->to('view', $post);
    Bouncer::refresh();

    livewire(EditRole::class, ['record' => $role->getKey()])
        ->assertSee(__('filament-bouncer::roles.form.restricted_owned'))
        ->assertSee(trans_choice('filament-bouncer::roles.form.restricted_records', 1, ['count' => 1]));
});

test('the header no longer offers deleting, which lives behind the kebab of the listing', function (): void {
    signInAsRoleManager();

    livewire(EditRole::class, ['record' => editedRole()->getKey()])
        ->assertActionDoesNotExist(TestAction::make('delete'))
        ->assertOk();
});

test('the screen reads what is known about the role beside what it may do', function (): void {
    config()->set('filament-bouncer.tenancy', true);

    signInAsRoleManager();

    livewire(EditRole::class, ['record' => editedRole()->getKey()])
        ->assertSee(__('filament-bouncer::roles.record.scope'))
        ->assertSee(__('filament-bouncer::roles.record.metadata'))
        ->assertSchemaComponentExists('scope', checkComponentUsing: fn (Component $component): bool => ! $component instanceof Field)
        ->assertOk();
});
