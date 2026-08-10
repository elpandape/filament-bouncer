<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ListRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

test('the list shows the roles that exist', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    Models::role()->newQuery()->create(['name' => 'editor']);
    Models::role()->newQuery()->create(['name' => 'reviewer']);

    livewire(ListRoles::class)
        ->assertCanSeeTableRecords(Models::role()->newQuery()->get())
        ->assertOk();
});

test('the row of the role that holds everything offers no way to edit or delete it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'owner');

    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $owner = Models::role()->newQuery()->create(['name' => 'owner']);
    $editor = Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(ListRoles::class)
        ->assertActionHidden(TestAction::make('edit')->table($owner))
        ->assertActionHidden(TestAction::make('delete')->table($owner))
        ->assertActionVisible(TestAction::make('edit')->table($editor));
});

test('Filament sends these actions to the pages instead of opening the form in a modal', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(ListRoles::class)
        ->assertActionHasUrl(TestAction::make('create'), RoleResource::getUrl('create'))
        ->assertActionHasUrl(TestAction::make('edit')->table($role), RoleResource::getUrl('edit', ['record' => $role]))
        ->assertActionHasUrl(TestAction::make('view')->table($role), RoleResource::getUrl('view', ['record' => $role]));
});

test('the resource takes how it presents itself from configuration', function (): void {
    config()->set('filament-bouncer.navigation.icon', 'heroicon-o-key');
    config()->set('filament-bouncer.navigation.group', 'Security');
    config()->set('filament-bouncer.navigation.sort', 7);
    config()->set('filament-bouncer.navigation.slug', 'access/roles');

    expect(RoleResource::getNavigationIcon())->toBe('heroicon-o-key')
        ->and(RoleResource::getNavigationGroup())->toBe('Security')
        ->and(RoleResource::getNavigationSort())->toBe(7)
        ->and(RoleResource::getSlug())->toBe('access/roles')
        ->and(RoleResource::getRecordTitleAttribute())->toBe('name');
});

test('the roles screen is governed by an ability like everything else', function (): void {
    signIn();

    livewire(ListRoles::class)->assertForbidden();
});

test('the roles screen becomes a row of the grid it draws', function (): void {
    $user = signInAsRoleManager();

    $role = Subject::keyFor(Models::classname(Role::class));

    livewire(CreateRole::class)
        ->assertOk();

    expect(offeredCells(gridState(livewire(CreateRole::class))))
        ->toContain("{$role}.viewAny")
        ->toContain("{$role}.delete")
        ->and($user->exists)->toBeTrue();
});
