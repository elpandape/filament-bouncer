<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability as CatalogAbility;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function signInAsAbilityReader(): Model
{
    /** @var class-string $ability */
    $ability = Models::classname(Ability::class);

    $user = signIn();

    grant($user, [
        ['viewAny', $ability],
        ['view', $ability],
        ['update', $ability],
        ['viewAny', Post::class],
    ]);

    return $user;
}

function signInAsAbilityAuthor(): Model
{
    /** @var class-string $ability */
    $ability = Models::classname(Ability::class);

    $user = signInAsAbilityReader();

    grant($user, [['create', $ability]]);

    return $user;
}

/**
 * A row narrowed the way the composer narrows one, written without going through it.
 */
function narrowedViewAny(bool $owned = true, ?int $record = null): Model
{
    /** @var Model $row */
    $row = Models::ability()->newQuery()->make();
    $row->forceFill([
        'name' => 'viewAny',
        'title' => 'Posts: See the list — narrowed',
        'entity_type' => Post::class,
        'entity_id' => $record,
        'only_owned' => $owned,
    ])->save();

    return $row;
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

test('narrowing an ability takes a grant of its own', function (): void {
    signInAsAbilityReader();

    expect(AbilityResource::canCreate())->toBeFalse();

    signInAsAbilityAuthor();

    expect(AbilityResource::canCreate())->toBeTrue();
});

test('it composes a rule holding only for what its holder owns', function (): void {
    signInAsAbilityAuthor();

    livewire(CreateAbility::class)
        ->fillForm([
            'subject' => Subject::keyFor(Post::class),
            'action' => 'update',
            'only_owned' => true,
            'title' => 'Posts they wrote',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    /** @var Model $row */
    $row = Models::ability()->newQuery()->where('title', 'Posts they wrote')->firstOrFail();

    expect($row->getAttribute('name'))->toBe('update')
        ->and($row->getAttribute('entity_type'))->toBe(Post::class)
        ->and($row->getAttribute('entity_id'))->toBeNull()
        ->and($row->getAttribute('only_owned'))->toBeTruthy();
});

test('it composes a rule holding for a single record', function (): void {
    signInAsAbilityAuthor();

    livewire(CreateAbility::class)
        ->fillForm([
            'subject' => Subject::keyFor(Post::class),
            'action' => 'delete',
            'entity_id' => 7,
            'title' => 'That one post',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    /** @var Model $row */
    $row = Models::ability()->newQuery()->where('title', 'That one post')->firstOrFail();

    /** @var scalar $record */
    $record = $row->getAttribute('entity_id');

    expect($row->getAttribute('name'))->toBe('delete')
        ->and((string) $record)->toBe('7')
        ->and($row->getAttribute('only_owned'))->toBeFalsy();
});

test('a rule that narrows nothing is the plain one, and is refused', function (): void {
    signInAsAbilityAuthor();

    livewire(CreateAbility::class)
        ->fillForm([
            'subject' => Subject::keyFor(Post::class),
            'action' => 'update',
            'title' => 'Every post there is',
        ])
        ->call('create')
        ->assertHasFormErrors(['only_owned']);

    expect(Models::ability()->newQuery()->where('title', 'Every post there is')->exists())->toBeFalse();
});

test('a second row saying the same thing is refused', function (): void {
    signInAsAbilityAuthor();
    narrowedViewAny();

    livewire(CreateAbility::class)
        ->fillForm([
            'subject' => Subject::keyFor(Post::class),
            'action' => 'viewAny',
            'only_owned' => true,
            'title' => 'The very same rule',
        ])
        ->call('create')
        ->assertHasFormErrors(['action']);

    expect(Models::ability()->newQuery()
        ->where('name', 'viewAny')
        ->where('entity_type', Post::class)
        ->where('only_owned', true)
        ->count())->toBe(1);
});

test('the name comes from the catalogue and not from the column it was picked in', function (): void {
    signInAsAbilityAuthor();

    livewire(CreateAbility::class)
        ->fillForm([
            'subject' => Subject::keyFor(Post::class),
            'action' => CatalogAbility::MANAGE_ACTION,
            'only_owned' => true,
            'title' => 'Anything with the posts they wrote',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    /** @var Model $row */
    $row = Models::ability()->newQuery()->where('title', 'Anything with the posts they wrote')->firstOrFail();

    expect($row->getAttribute('name'))->toBe(CatalogAbility::MANAGE_NAME)
        ->and($row->getAttribute('entity_type'))->toBe(Post::class);
});

test('a pair the catalogue cannot resolve is refused, not stored', function (): void {
    signInAsAbilityAuthor();

    livewire(CreateAbility::class)
        ->fillForm([
            'subject' => Subject::keyFor(Post::class),
            'action' => 'update',
            'only_owned' => true,
            'title' => 'From nowhere',
        ])
        ->set('data.action', 'no-such-action')
        ->set('data.title', 'From nowhere')
        ->call('create')
        ->assertHasFormErrors();

    expect(Models::ability()->newQuery()->where('title', 'From nowhere')->exists())->toBeFalse();
});

test('a narrowed rule is handed out as itself, and leaves the plain one alone', function (): void {
    signInAsAbilityAuthor();
    $plain = storedViewAny();
    $narrowed = narrowedViewAny();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(EditAbility::class, ['record' => $narrowed->getKey()])
        ->fillForm([AbilityForm::HOLDERS.'.'.roleKey($role) => Stance::Granted->value])
        ->call('save')
        ->assertHasNoFormErrors();

    Bouncer::refresh();

    $abilities = app(RoleAbilities::class);

    expect($abilities->stanceOnRow($role, $narrowed))->toBe(Stance::Granted)
        ->and($abilities->stanceOnRow($role, $plain))->toBe(Stance::Neutral)
        ->and(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(abilityCount($role))->toBe(1);
});

test('a narrowed rule can be taken back, and says so on the way', function (): void {
    signInAsAbilityAuthor();
    $narrowed = narrowedViewAny(owned: false, record: 3);

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    $abilities = app(RoleAbilities::class);
    $abilities->saveRow($role, $narrowed, Stance::Forbidden);
    $abilities->saveRow($role, $narrowed, Stance::Forbidden);

    expect($abilities->stanceOnRow($role, $narrowed))->toBe(Stance::Forbidden)
        ->and(abilityCount($role))->toBe(1);

    livewire(EditAbility::class, ['record' => $narrowed->getKey()])
        ->assertSee(__('filament-bouncer::abilities.narrowed_legend'))
        ->fillForm([AbilityForm::HOLDERS.'.'.roleKey($role) => Stance::Neutral->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($abilities->stanceOnRow($role, $narrowed))->toBe(Stance::Neutral)
        ->and(abilityCount($role))->toBe(0);
});

test('the list tells a narrowed row from one nothing declares, and says who holds it', function (): void {
    signInAsAbilityAuthor();
    storedViewAny();
    narrowedViewAny(owned: false, record: 3);
    $owned = narrowedViewAny();

    /** @var Model $suelta */
    $suelta = Models::ability()->newQuery()->make();
    $suelta->forceFill(['name' => 'invented-by-hand', 'title' => 'Invented by hand'])->save();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'ribereno']);
    app(RoleAbilities::class)->saveRow($role, $owned, Stance::Granted);

    livewire(ListAbilities::class)
        ->assertSee(__('filament-bouncer::abilities.declared_yes'))
        ->assertSee(__('filament-bouncer::abilities.declared_no'))
        ->assertSee(__('filament-bouncer::abilities.declared_apart'))
        ->assertSee(__('filament-bouncer::abilities.record_suffix', ['id' => '3']))
        ->assertSee(__('filament-bouncer::abilities.owned_suffix'))
        ->assertSee('ribereno');
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
