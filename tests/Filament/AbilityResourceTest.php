<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function signInAsAbilityReader(): void
{
    /** @var class-string $ability */
    $ability = Models::classname(Ability::class);

    grant(signIn(), [
        ['viewAny', $ability],
        ['view', $ability],
        ['update', $ability],
        ['viewAny', Post::class],
    ]);
}

function roleKey(Model $role): string
{
    /** @var scalar $key */
    $key = $role->getKey();

    return (string) $key;
}

function storedViewAny(): Model
{
    /** @var Model $row */
    $row = Models::ability()->newQuery()->make();
    $row->forceFill([
        'name' => 'viewAny',
        'title' => 'Posts: See the list',
        'entity_type' => Post::class,
    ])->save();

    return $row;
}

test('nothing creates an ability from a form', function (): void {
    signInAsAbilityReader();

    expect(AbilityResource::canCreate())->toBeFalse();
});

test('the screen takes a grant of its own, like everything else', function (): void {
    signIn();

    expect(AbilityResource::canViewAny())->toBeFalse();

    signInAsAbilityReader();

    expect(AbilityResource::canViewAny())->toBeTrue();
});

test('the list shows what the reader may decide about', function (): void {
    signInAsAbilityReader();
    storedViewAny();

    livewire(ListAbilities::class)
        ->assertCanSeeTableRecords(Models::ability()->newQuery()->where('name', 'viewAny')->get())
        ->assertOk();
});

test('the title is the one field it writes, and the name is not', function (): void {
    signInAsAbilityReader();
    $row = storedViewAny();

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->assertFormFieldDisabled('name')
        ->assertFormFieldDisabled('entity_type')
        ->fillForm(['title' => 'Artículos: ver el listado'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($row->refresh()->getAttribute('title'))->toBe('Artículos: ver el listado')
        ->and($row->getAttribute('name'))->toBe('viewAny');
});

test('a stance set from this end is the same row the roles screen writes', function (): void {
    signInAsAbilityReader();
    $row = storedViewAny();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->fillForm([AbilityForm::HOLDERS.'.'.roleKey($role) => Stance::Granted->value])
        ->call('save')
        ->assertHasNoFormErrors();

    Bouncer::refresh();

    expect(holds($role, 'viewAny', Post::class))->toBeTrue();
});

test('a row the code no longer declares carries no cells', function (): void {
    signInAsAbilityReader();

    /** @var Model $huerfana */
    $huerfana = Models::ability()->newQuery()->make();
    $huerfana->forceFill(['name' => 'no-longer-declared', 'title' => 'No longer declared'])->save();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(EditAbility::class, ['record' => $huerfana->getKey()])
        ->assertSee(__('filament-bouncer::abilities.withheld'))
        ->fillForm(['title' => 'Renamed all the same'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(abilityCount($role))->toBe(0);
});

test('the screen takes its place in the panel from configuration', function (): void {
    config()->set('filament-bouncer.abilities.icon', 'heroicon-o-key');
    config()->set('filament-bouncer.abilities.sort', 7);
    config()->set('filament-bouncer.abilities.slug', 'seguridad/habilidades');
    config()->set('filament-bouncer.navigation.group', 'Seguridad');

    expect(AbilityResource::getNavigationIcon())->toBe('heroicon-o-key')
        ->and(AbilityResource::getNavigationSort())->toBe(7)
        ->and(AbilityResource::getSlug())->toBe('seguridad/habilidades')
        ->and(AbilityResource::getNavigationGroup())->toBe('Seguridad')
        ->and(AbilityResource::getRecordTitleAttribute())->toBe('title');
});

test('the list says who holds it, how, and which rows the code no longer declares', function (): void {
    signInAsAbilityReader();
    storedViewAny();

    /** @var Model $suelta */
    $suelta = Models::ability()->newQuery()->make();
    $suelta->forceFill(['name' => 'invented-by-hand', 'title' => 'Invented by hand'])->save();

    /** @var Model $amplio */
    $amplio = Models::role()->newQuery()->create(['name' => 'amplio']);
    Bouncer::allow($amplio)->toManage(Post::class);

    /** @var Model $directo */
    $directo = Models::role()->newQuery()->create(['name' => 'directo']);
    Bouncer::allow($directo)->to('viewAny', Post::class);
    Bouncer::refresh();

    livewire(ListAbilities::class)
        ->assertSee(__('filament-bouncer::abilities.broader_short'))
        ->assertSee(__('filament-bouncer::abilities.declared_no'))
        ->assertSee(__('filament-bouncer::abilities.declared_yes'));
});

test('a form built without a record asks the catalogue for nothing', function (): void {
    signInAsAbilityReader();

    livewire(ListAbilities::class)->assertOk();

    expect(AbilityResource::canCreate())->toBeFalse();
});

test('a role deleted while the screen was open is passed over, not resurrected', function (): void {
    signInAsAbilityReader();
    $row = storedViewAny();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'efimero']);

    $screen = livewire(EditAbility::class, ['record' => $row->getKey()])
        ->fillForm([AbilityForm::HOLDERS.'.'.roleKey($role) => Stance::Granted->value]);

    $id = $role->getKey();
    $role->delete();

    $screen->call('save')->assertHasNoFormErrors();

    expect(Models::role()->newQuery()->find($id))->toBeNull();
});
