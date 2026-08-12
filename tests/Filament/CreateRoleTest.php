<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
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

test('the screen says up front that nothing is saved until the end', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)->assertSee(__('filament-bouncer::roles.wizard.subtitle'));
});

test('the identity step explains the two names and hints each field', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->assertSee(__('filament-bouncer::roles.wizard.identity_heading'))
        ->assertSee(__('filament-bouncer::roles.wizard.identity_note'))
        ->assertSee(__('filament-bouncer::roles.form.name_placeholder'))
        ->assertSee(__('filament-bouncer::roles.form.title_placeholder'));
});

test('the wizard is marked for the stylesheet that seats its footer', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)->assertSeeHtml('fb-wizard');
});

test('the reserved name is warned about where the name is being chosen', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    livewire(CreateRole::class)
        ->assertSeeHtml('fb-protected-notice')
        ->assertSeeHtml('<b>super-admin</b>');
});

test('with no protected role configured there is nothing to warn about', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)->assertDontSeeHtml('fb-protected-notice');
});

test('the last step reads back the choice, subject by subject', function (): void {
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
        ->assertSeeHtml('fb-rev-chip-granted')
        ->assertSeeHtml('fb-rev-chip-forbidden')
        ->assertSee(__('filament-bouncer::actions.viewAny'))
        ->assertSee(trans_choice('filament-bouncer::roles.summary.granted', 1, ['count' => 1]))
        ->assertSee(trans_choice('filament-bouncer::roles.summary.neutral', $cells - 2, ['count' => $cells - 2]));
});

test('a subject nobody said anything about still gets its line', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            RoleForm::ABILITIES => [Subject::keyFor(Post::class) => ['viewAny' => Stance::Granted->value]],
        ])
        ->assertSee(__('filament-bouncer::roles.review.silent'));
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
        ->fillForm([
            'name' => 'editor',
            'abilities' => [Subject::keyFor(Post::class) => ['viewAny' => Stance::Granted->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Models::role()->newQuery()->where('name', 'editor')->exists())->toBeTrue();
});

test('a role saying nothing about anything is refused', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm(['name' => 'mudo'])
        ->call('create')
        ->assertHasFormErrors([RoleForm::ABILITIES]);

    expect(Models::role()->newQuery()->where('name', 'mudo')->exists())->toBeFalse();
});

test('one stance is enough to get past the refusal', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'lector',
            'abilities' => [Subject::keyFor(Post::class) => ['viewAny' => Stance::Granted->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Models::role()->newQuery()->where('name', 'lector')->exists())->toBeTrue();
});

test('a denial also counts as saying something', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'vetado',
            'abilities' => [Subject::keyFor(Post::class) => ['delete' => Stance::Forbidden->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Models::role()->newQuery()->where('name', 'vetado')->exists())->toBeTrue();
});
test('the abilities step heads itself and carries the grid', function (): void {
    signInAsRoleManager();

    $html = livewire(CreateRole::class)->html();

    expect($html)->toContain(__('filament-bouncer::roles.wizard.abilities_heading'))
        ->and($html)->toContain(__('filament-bouncer::roles.wizard.abilities_note'))
        ->and($html)->toContain('fb-seg');
});

test('a grant covering a whole model is read back by its own name', function (): void {
    signInAsRoleManager();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'editor',
            RoleForm::ABILITIES => [
                Subject::keyFor(Post::class) => [Ability::MANAGE_ACTION => Stance::Granted->value],
            ],
        ])
        ->assertSee(__('filament-bouncer::roles.form.manage'))
        ->assertSeeHtml('fb-rev-chip-granted');
});
