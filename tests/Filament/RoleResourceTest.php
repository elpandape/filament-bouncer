<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ListRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\ResourceGroup;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\ResourceIcon;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

pest()->extend(TestCase::class);

function resourceCatalog(): Catalog
{
    return app(CatalogRegistry::class)->current();
}

function resourceRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

test('the screen is about whatever role model the application configured', function (): void {
    expect(RoleResource::getModel())->toBe(Models::classname(Role::class))
        ->and(RoleResource::getRecordTitleAttribute())->toBe('name');
});

test('a role is called a role in whichever tongue the panel is read in', function (): void {
    expect(RoleResource::getModelLabel())->toBe(__('filament-bouncer::roles.resource.label'))
        ->and(RoleResource::getPluralModelLabel())->toBe(__('filament-bouncer::roles.resource.plural'));

    app()->setLocale('es');

    expect(RoleResource::getModelLabel())->toBe('Rol');
});

test('it presents itself the way the package ships when nothing says otherwise', function (): void {
    expect(RoleResource::getSlug())->toBe('security/roles')
        ->and(RoleResource::getNavigationIcon())->toBeNull()
        ->and(RoleResource::getNavigationGroup())->toBeNull()
        ->and(RoleResource::getNavigationSort())->toBeNull();
});

test('it takes from configuration how it presents itself', function (): void {
    config()->set('filament-bouncer.navigation', [
        'icon' => 'heroicon-o-shield-check',
        'group' => 'Access',
        'sort' => 7,
        'slug' => 'access/roles',
    ]);

    expect(RoleResource::getSlug())->toBe('access/roles')
        ->and(RoleResource::getNavigationIcon())->toBe('heroicon-o-shield-check')
        ->and(RoleResource::getNavigationGroup())->toBe('Access')
        ->and(RoleResource::getNavigationSort())->toBe(7);
});

test('it is read and changed on pages rather than in a modal', function (): void {
    expect(array_keys(RoleResource::getPages()))->toBe(['index', 'create', 'view', 'edit'])
        ->and(ListRoles::getResource())->toBe(RoleResource::class)
        ->and(CreateRole::getResource())->toBe(RoleResource::class)
        ->and(ViewRole::getResource())->toBe(RoleResource::class)
        ->and(EditRole::getResource())->toBe(RoleResource::class);
});

test('the roles screen is itself a row of the catalogue it draws', function (): void {
    $entity = resourceCatalog()->entity(Entity::keyFor(Models::classname(Role::class)));

    expect($entity)->not->toBeNull()
        ->and(array_keys($entity->abilities ?? []))
        ->toBe(['create', 'delete', 'update', 'view', 'viewAny']);
});

test('the screen is governed by an ability like everything else', function (): void {
    signIn();

    expect(RoleResource::canAccess())->toBeFalse();
});

test('somebody granted the screen may work it', function (): void {
    signInAsRoleManager();

    expect(RoleResource::canAccess())->toBeTrue();
});

test('an ordinary role may be edited and deleted', function (): void {
    signInAsRoleManager();

    $role = resourceRole();

    expect(RoleResource::canEdit($role))->toBeTrue()
        ->and(RoleResource::canDelete($role))->toBeTrue();
});

test('the way back in is not editable, whoever is asking', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $privileged = resourceRole('super-admin');

    expect(RoleResource::canEdit($privileged))->toBeFalse()
        ->and(RoleResource::canDelete($privileged))->toBeFalse();
});

test('nobody works on a role they hold themselves', function (): void {
    $reader = signInAsRoleManager();

    $mine = resourceRole();
    Bouncer::assign('editor')->to($reader);
    Bouncer::refresh();

    expect(RoleResource::canEdit($mine))->toBeFalse()
        ->and(RoleResource::canDelete($mine))->toBeFalse();
});

test('a refusal of the abilities still refuses, whoever holds no role', function (): void {
    signIn();

    expect(RoleResource::canEdit(resourceRole()))->toBeFalse()
        ->and(RoleResource::canDelete(resourceRole('reviewer')))->toBeFalse();
});

test('an icon named as an enum reaches the sidebar instead of a type error', function (): void {
    config()->set('filament-bouncer.navigation.icon', ResourceIcon::Shield);
    config()->set('filament-bouncer.navigation.group', ResourceGroup::Security);

    expect(RoleResource::getNavigationIcon())->toBe(ResourceIcon::Shield)
        ->and(RoleResource::getNavigationGroup())->toBe(ResourceGroup::Security);
});
