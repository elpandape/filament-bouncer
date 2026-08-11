<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function createdRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->where('name', $name)->firstOrFail();

    return $role;
}

test('the catalogue is offered whole, however little the person filling it in holds', function (): void {
    $editor = signInAsRoleManager();

    /** @var array<string, array<string, string>> $state */
    $state = livewire(CreateRole::class)->get('data.'.RoleForm::ABILITIES);

    expect($state[Subject::keyFor(Post::class)]['delete'] ?? null)->toBe(Stance::Neutral->value)
        ->and(holds($editor, 'delete', Post::class))->toBeFalse();
});

test('creating a role grants exactly what was ticked', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            RoleForm::ABILITIES => [
                Subject::keyFor(Post::class) => [
                    'viewAny' => Stance::Granted->value,
                    'delete' => Stance::Neutral->value,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = createdRole();

    expect(holds($role, 'viewAny', Post::class))->toBeTrue()
        ->and(holds($role, 'delete', Post::class))->toBeFalse();
});

test('a cell the panel does not declare is thrown away', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            RoleForm::ABILITIES => ['made-up-subject' => ['made-up-action' => Stance::Granted->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(abilityCount(createdRole()))->toBe(0);
});

test('a role has to be named', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm(['name' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('a name somebody has already taken is refused', function (): void {
    signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('somebody holding nothing of their own hands out just the same', function (): void {
    $editor = signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            RoleForm::ABILITIES => [
                Subject::keyFor(Post::class) => ['forceDelete' => Stance::Granted->value],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(holds(createdRole(), 'forceDelete', Post::class))->toBeTrue()
        ->and(holds($editor, 'forceDelete', Post::class))->toBeFalse();
});

test('it is composed one question at a time, ending on what is about to be written', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->assertSee(__('filament-bouncer::roles.wizard.identity'))
        ->assertSee(__('filament-bouncer::roles.wizard.abilities'))
        ->assertSee(__('filament-bouncer::roles.wizard.review'));
});

test('the last step reads back what the first two chose', function (): void {
    signInAsRoleManager();

    $cells = collect(app(CatalogRegistry::class)->current()->subjects)
        ->sum(static fn (Subject $subject): int => count($subject->cells()));

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            RoleForm::ABILITIES => [
                Subject::keyFor(Post::class) => [
                    'viewAny' => Stance::Granted->value,
                    'delete' => Stance::Forbidden->value,
                ],
            ],
        ])
        ->assertSee(__('filament-bouncer::roles.wizard.reading', [
            'name' => 'editor',
            'granted' => 1,
            'forbidden' => 1,
            'total' => $cells,
        ]));
});

test('the name of the role that holds everything cannot be taken from this screen', function (): void {
    config()->set('filament-bouncer.privileged_role', 'owner');

    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm(['name' => 'owner'])
        ->call('create')
        ->assertHasFormErrors(['name']);

    expect(Models::role()->newQuery()->where('name', 'owner')->exists())->toBeFalse();
});

test('any other name is still free', function (): void {
    config()->set('filament-bouncer.privileged_role', 'owner');

    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm(['name' => 'editor'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Models::role()->newQuery()->where('name', 'editor')->exists())->toBeTrue();
});
