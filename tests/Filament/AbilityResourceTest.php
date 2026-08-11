<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ViewAbility;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function resourceAbility(): Model
{
    Bouncer::allow('editor')->to('update', Post::class);

    /** @var Model $row */
    $row = Models::ability()->newQuery()->where('name', 'update')->firstOrFail();

    return $row;
}

test('the screen is about whatever ability model the application configured', function (): void {
    expect(AbilityResource::getModel())->toBe(Models::classname(Ability::class))
        ->and(AbilityResource::getRecordTitleAttribute())->toBe('name');
});

test('an ability is called an ability in whichever tongue the panel is read in', function (): void {
    expect(AbilityResource::getModelLabel())->toBe(__('filament-bouncer::abilities.resource.label'))
        ->and(AbilityResource::getPluralModelLabel())->toBe(__('filament-bouncer::abilities.resource.plural'));

    app()->setLocale('es');

    expect(AbilityResource::getModelLabel())->toBe('Habilidad');
});

test('it presents itself the way the package ships when nothing says otherwise', function (): void {
    expect(AbilityResource::getSlug())->toBe('security/abilities')
        ->and(AbilityResource::getNavigationIcon())->toBeNull()
        ->and(AbilityResource::getNavigationGroup())->toBeNull()
        ->and(AbilityResource::getNavigationSort())->toBeNull();
});

test('it takes its place in the panel from configuration', function (): void {
    config()->set('filament-bouncer.abilities', [
        'icon' => 'heroicon-o-key',
        'sort' => 8,
        'slug' => 'access/abilities',
    ]);
    config()->set('filament-bouncer.navigation.group', 'Access');

    expect(AbilityResource::getSlug())->toBe('access/abilities')
        ->and(AbilityResource::getNavigationIcon())->toBe('heroicon-o-key')
        ->and(AbilityResource::getNavigationSort())->toBe(8)
        ->and(AbilityResource::getNavigationGroup())->toBe('Access');
});

test('it shares its group with the roles screen, because they are two ends of one thing', function (): void {
    config()->set('filament-bouncer.navigation.group', 'Access');

    expect(AbilityResource::getNavigationGroup())->toBe('Access');
});

test('it is read and changed on pages rather than in a modal', function (): void {
    expect(array_keys(AbilityResource::getPages()))->toBe(['index', 'create', 'view', 'edit'])
        ->and(ListAbilities::getResource())->toBe(AbilityResource::class)
        ->and(CreateAbility::getResource())->toBe(AbilityResource::class)
        ->and(ViewAbility::getResource())->toBe(AbilityResource::class)
        ->and(EditAbility::getResource())->toBe(AbilityResource::class);
});

test('the abilities screen is itself a row of the catalogue it lists', function (): void {
    $subject = app(CatalogRegistry::class)->current()->subject(Subject::keyFor(Models::classname(Ability::class)));

    expect($subject)->not->toBeNull()
        ->and(array_keys($subject->abilities ?? []))
        ->toBe(['create', 'update', 'view', 'viewAny']);
});

test('the screen is governed by an ability like everything else', function (): void {
    signIn();

    expect(AbilityResource::canAccess())->toBeFalse();
});

test('somebody granted the screen may work it', function (): void {
    signInAsAbilityManager();

    expect(AbilityResource::canAccess())->toBeTrue();
});

test('narrowing a rule takes a grant of its own, beyond reading the screen', function (): void {
    $reader = signIn();

    /** @var class-string $model */
    $model = Models::classname(Ability::class);

    grant($reader, [['viewAny', $model]]);

    expect(AbilityResource::canAccess())->toBeTrue()
        ->and(AbilityResource::canCreate())->toBeFalse();
});

test('somebody granted the narrowing may narrow', function (): void {
    signInAsAbilityManager();

    expect(AbilityResource::canCreate())->toBeTrue();
});

test('no row is ever offered for deletion, whoever is asking and whatever they hold', function (): void {
    $reader = signInAsAbilityManager();

    Bouncer::allow($reader)->everything();
    Bouncer::refresh();

    expect(AbilityResource::canDelete(resourceAbility()))->toBeFalse()
        ->and(AbilityResource::canDeleteAny())->toBeFalse();
});
