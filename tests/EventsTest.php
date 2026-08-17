<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Events\AbilityRef;
use ElPandaPe\FilamentBouncer\Events\AbilityStanceChangedEvent;
use ElPandaPe\FilamentBouncer\Events\CatalogReconciledEvent;
use ElPandaPe\FilamentBouncer\Events\PrivilegedRoleRestoredEvent;
use ElPandaPe\FilamentBouncer\Events\RoleAssignedEvent;
use ElPandaPe\FilamentBouncer\Events\RoleDeletedEvent;
use ElPandaPe\FilamentBouncer\Events\RoleRetractedEvent;
use ElPandaPe\FilamentBouncer\Filament\Forms\RolesField;
use ElPandaPe\FilamentBouncer\Filament\RelationManagers\RolesRelationManager;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Causer;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users\Pages\ViewUser;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

afterEach(function (): void {
    Relation::morphMap([], false);
    Relation::requireMorphMap(false);
});

test('an ability is the same rule read from the catalogue and read from its stored row', function (): void {
    Relation::enforceMorphMap(['post' => Post::class]);

    $catalogued = Ability::forModel(Post::class, 'view', 'Posts: View', AbilityScope::Read);

    $row = Models::ability();
    $row->forceFill($catalogued->attributes())->save();

    expect(AbilityRef::fromCatalog($catalogued)->identity())
        ->toBe(AbilityRef::fromRow($row)->identity())
        ->and(AbilityRef::fromCatalog($catalogued)->entityMorphClass)->toBe('post')
        ->and(AbilityRef::fromRow($row)->entityMorphClass)->toBe('post');
});

test('an ability fenced to one record says so, and carries the words a reader needs', function (): void {
    $row = Models::ability();
    $row->forceFill([
        'name' => 'view',
        'title' => 'Posts: View',
        'entity_type' => Post::class,
        'entity_id' => 7,
        'only_owned' => true,
    ])->save();

    $ref = AbilityRef::fromRow($row);

    expect($ref->entityId)->toBe(7)
        ->and($ref->onlyOwned)->toBeTrue()
        ->and($ref->title)->toBe('Posts: View')
        ->and($ref->describe())->toBe('view on '.Post::class);
});

test('an ability about no model at all is described by its bare name', function (): void {
    $row = Models::ability();
    $row->forceFill(['name' => 'impersonate-users', 'title' => ''])->save();

    $ref = AbilityRef::fromRow($row);

    expect($ref->entityMorphClass)->toBeNull()
        ->and($ref->title)->toBeNull()
        ->and($ref->describe())->toBe('impersonate-users');
});

test('every event carries its subject, and an author that may be nobody', function (): void {
    $account = signIn();
    $causer = User::forceCreate([
        'name' => 'Causer User',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);
    $ref = new AbilityRef('view', 'post', null, false, null, 'Posts: View');

    $assigned = new RoleAssignedEvent($account, 'editor', $causer);
    $retracted = new RoleRetractedEvent($account, 'editor', null);
    $changed = new AbilityStanceChangedEvent($account, $ref, Stance::Neutral, Stance::Granted, $causer);
    /** @var Collection<int, Model> */
    $holders = new Collection([$account]);
    $deleted = new RoleDeletedEvent('editor', $holders, 3, $causer);
    $restored = new PrivilegedRoleRestoredEvent('super-admin', null);
    $reconciled = new CatalogReconciledEvent(4, 2, null);

    expect($assigned->authority->is($account))->toBeTrue()
        ->and($assigned->causer?->is($causer))->toBeTrue()
        ->and($assigned->causer?->isNot($account))->toBeTrue()
        ->and($retracted->authority->is($account))->toBeTrue()
        ->and($retracted->causer)->toBeNull()
        ->and($changed->authority->is($account))->toBeTrue()
        ->and($changed->causer?->is($causer))->toBeTrue()
        ->and($changed->from)->toBe(Stance::Neutral)
        ->and($changed->to)->toBe(Stance::Granted)
        ->and($changed->ability->identity())->toBe($ref->identity())
        ->and($deleted->holders)->toHaveCount(1)
        ->and($deleted->causer?->is($causer))->toBeTrue()
        ->and($restored->role)->toBe('super-admin')
        ->and($reconciled->written)->toBe(4)
        ->and($reconciled->pruned)->toBe(2);
});

test('the author of a write is whoever is signed in, and nobody outside a panel', function (): void {
    expect(Causer::current())->toBeNull();

    $user = signIn();

    expect(Causer::current()?->is($user))->toBeTrue();
});

test('a command speaks for nobody, and says so', function (): void {
    signIn();

    /** @var Panel $panel */
    $panel = Filament::getPanel('test');
    $panel->default(false);

    Filament::setCurrentPanel(null);

    expect(Causer::current())->toBeNull();
});

test('handing a role out on the form that creates an account is said out loud', function (): void {
    $editor = signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    RolesField::assign($account, ['auditor']);

    Event::assertDispatched(RoleAssignedEvent::class, fn (RoleAssignedEvent $event): bool => $event->authority->is($account)
        && $event->role === 'auditor'
        && $event->causer?->is($editor) === true);
});

test('a role the screen would never have offered is written by nobody and said by nobody', function (): void {
    signInAsRoleManager();

    Event::fake();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    RolesField::assign($account, ['a-role-that-does-not-exist']);

    Event::assertNotDispatched(RoleAssignedEvent::class);
});

test('handing a role out from the tab is said out loud', function (): void {
    $editor = signInAsRoleManager();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ])->callAction(TestAction::make('assign')->table(), ['role' => 'auditor']);

    Event::assertDispatched(RoleAssignedEvent::class, fn (RoleAssignedEvent $event): bool => $event->authority->is($account)
        && $event->role === 'auditor'
        && $event->causer?->is($editor) === true);
});

test('taking a role away from the tab is said out loud', function (): void {
    $editor = signInAsRoleManager();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    Bouncer::assign('auditor')->to($account);
    Bouncer::refresh();

    Event::fake();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ])->callAction(TestAction::make('retract')->table($role));

    Event::assertDispatched(RoleRetractedEvent::class, fn (RoleRetractedEvent $event): bool => $event->authority->is($account)
        && $event->role === 'auditor'
        && $event->causer?->is($editor) === true);
});
