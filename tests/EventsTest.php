<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Events\AbilityRef;
use ElPandaPe\FilamentBouncer\Events\AbilityStanceChangedEvent;
use ElPandaPe\FilamentBouncer\Events\CatalogReconciledEvent;
use ElPandaPe\FilamentBouncer\Events\PrivilegedRoleRestoredEvent;
use ElPandaPe\FilamentBouncer\Events\RoleAssignedEvent;
use ElPandaPe\FilamentBouncer\Events\RoleDeletedEvent;
use ElPandaPe\FilamentBouncer\Events\RoleRetractedEvent;
use ElPandaPe\FilamentBouncer\Filament\Forms\RolesField;
use ElPandaPe\FilamentBouncer\Filament\RelationManagers\RolesRelationManager;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ListRoles;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Causer;
use ElPandaPe\FilamentBouncer\Support\RoleHolding;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

test('out of a mixed request, only the role that was actually written is said', function (): void {
    $editor = signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    RolesField::assign($account, ['auditor', 'a-role-that-does-not-exist']);

    Event::assertDispatchedTimes(RoleAssignedEvent::class, 1);

    Event::assertDispatched(RoleAssignedEvent::class, fn (RoleAssignedEvent $event): bool => $event->authority->is($account)
        && $event->role === 'auditor'
        && $event->causer?->is($editor) === true);
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

test('handing the tab a role the account already holds says it once, not twice', function (): void {
    signInAsRoleManager();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    $component = livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ]);

    $component->callAction(TestAction::make('assign')->table(), ['role' => 'auditor']);
    $component->callAction(TestAction::make('assign')->table(), ['role' => 'auditor']);

    Event::assertDispatchedTimes(RoleAssignedEvent::class, 1);
});

test('nobody holds a role that has not been created yet', function (): void {
    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    expect(RoleHolding::of($account, 'a-role-that-does-not-exist'))->toBeFalse();
});

test('the command that hands a role out speaks for nobody', function (): void {
    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => 'sisa@example.test',
        'password' => 'irrelevant',
    ]);

    Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    expect(Artisan::call('filament-bouncer:assign', ['role' => 'auditor', 'user' => 'sisa@example.test']))->toBe(0);

    Event::assertDispatched(RoleAssignedEvent::class, fn (RoleAssignedEvent $event): bool => $event->authority->is($account)
        && $event->role === 'auditor'
        && ! $event->causer instanceof Model);
});

test('a command that assigns nothing says nothing', function (): void {
    Event::fake();

    expect(Artisan::call('filament-bouncer:assign', ['role' => 'nope', 'user' => 'nobody@example.test']))->toBe(1);

    Event::assertNotDispatched(RoleAssignedEvent::class);
});

test('a command handing out a role the account already holds says it once, not twice', function (): void {
    User::forceCreate([
        'name' => 'Sisa',
        'email' => 'sisa@example.test',
        'password' => 'irrelevant',
    ]);

    Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    expect(Artisan::call('filament-bouncer:assign', ['role' => 'auditor', 'user' => 'sisa@example.test']))->toBe(0)
        ->and(Artisan::call('filament-bouncer:assign', ['role' => 'auditor', 'user' => 'sisa@example.test']))->toBe(0);

    Event::assertDispatchedTimes(RoleAssignedEvent::class, 1);
});

test('a cell of the grid that changes is said out loud', function (): void {
    $editor = signInAsRoleManager();

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    Event::fake();

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['abilities' => [Entity::keyFor(Post::class) => ['view' => Stance::Granted->value]]])
        ->call('save');

    Event::assertDispatched(AbilityStanceChangedEvent::class, fn (AbilityStanceChangedEvent $event): bool => $event->authority->is($role)
        && $event->ability->name === 'view'
        && $event->from === Stance::Neutral
        && $event->to === Stance::Granted
        && $event->causer?->is($editor) === true);
});

test('a cell saved with the stance it already had says nothing', function (): void {
    signInAsRoleManager();

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['abilities' => [Entity::keyFor(Post::class) => ['view' => Stance::Granted->value]]])
        ->call('save');

    Event::fake();

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['abilities' => [Entity::keyFor(Post::class) => ['view' => Stance::Granted->value]]])
        ->call('save');

    Event::assertNotDispatched(AbilityStanceChangedEvent::class);
});

test('forbidding is a stance of its own, and comes back to neutral by name', function (): void {
    signInAsRoleManager();

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    /** @var Model $ability */
    $ability = Models::ability()->newQuery()->where('name', 'view')->where('entity_type', Post::class)->firstOrFail();

    Event::fake();

    app(RoleAbilities::class)->saveRow($role, $ability, Stance::Forbidden);
    app(RoleAbilities::class)->saveRow($role, $ability, Stance::Neutral);

    Event::assertDispatchedTimes(AbilityStanceChangedEvent::class, 2);

    Event::assertDispatched(AbilityStanceChangedEvent::class, fn (AbilityStanceChangedEvent $event): bool => $event->from === Stance::Neutral
        && $event->to === Stance::Forbidden
        && $event->ability->name === 'view'
        && $event->ability->entityMorphClass === Post::class);

    Event::assertDispatched(AbilityStanceChangedEvent::class, fn (AbilityStanceChangedEvent $event): bool => $event->from === Stance::Forbidden
        && $event->to === Stance::Neutral
        && $event->ability->name === 'view'
        && $event->ability->entityMorphClass === Post::class);
});

test('a listener asking the gate from inside its handler sees the grid already finished', function (): void {
    signInAsRoleManager();

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    $seenComplete = [];

    Event::listen(AbilityStanceChangedEvent::class, function () use ($role, &$seenComplete): void {
        $seenComplete[] = holds($role, 'viewAny', Post::class) && holds($role, 'create', Post::class);
    });

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['abilities' => [Entity::keyFor(Post::class) => [
            'viewAny' => Stance::Granted->value,
            'create' => Stance::Granted->value,
        ]]])
        ->call('save');

    expect($seenComplete)->toBe([true, true]);
});

test('deleting a role says whose it was and how much went with it', function (): void {
    $editor = signInAsRoleManager();

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    $holder = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Bouncer::assign('auditor')->to($holder);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    Event::fake();

    livewire(ListRoles::class)->callAction(TestAction::make('delete')->table($role));

    Event::assertDispatchedTimes(RoleDeletedEvent::class, 1);

    Event::assertDispatched(RoleDeletedEvent::class, fn (RoleDeletedEvent $event): bool => $event->role === 'auditor'
        && $event->holders->count() === 1
        && $event->holders->first()?->is($holder) === true
        && $event->abilities === 1
        && $event->causer?->is($editor) === true);
});

test('deleting a role stops it answering yes in the same breath', function (): void {
    signInAsRoleManager();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    $holder = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Bouncer::assign('auditor')->to($holder);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    expect(holds($holder, 'view', Post::class))->toBeTrue();

    livewire(ListRoles::class)->callAction(TestAction::make('delete')->table($role));

    expect(holds($holder, 'view', Post::class))->toBeFalse();
});

test('a holder whose morph type cannot be resolved to a model is left out of what a role deletion says', function (): void {
    $editor = signInAsRoleManager();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    $holder = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Bouncer::assign('auditor')->to($holder);
    Bouncer::refresh();

    DB::table(Models::table('assigned_roles'))->insert([
        ['role_id' => $role->getKey(), 'entity_id' => 1, 'entity_type' => 'App\\Models\\Gone'],
        ['role_id' => $role->getKey(), 'entity_id' => 1, 'entity_type' => stdClass::class],
    ]);

    Event::fake();

    livewire(ListRoles::class)->callAction(TestAction::make('delete')->table($role));

    Event::assertDispatched(RoleDeletedEvent::class, fn (RoleDeletedEvent $event): bool => $event->role === 'auditor'
        && $event->holders->count() === 1
        && $event->holders->first()?->is($holder) === true
        && $event->causer?->is($editor) === true);
});

test('deleting a role with many holders of the same type reads them in one query, not one per holder', function (): void {
    signInAsRoleManager();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    $holderCount = 20;

    for ($i = 0; $i < $holderCount; $i++) {
        $holder = User::forceCreate([
            'name' => "Holder {$i}",
            'email' => Str::random(12).'@example.test',
            'password' => 'irrelevant',
        ]);

        Bouncer::assign('auditor')->to($holder);
    }

    Bouncer::refresh();

    Event::fake();

    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    livewire(ListRoles::class)->callAction(TestAction::make('delete')->table($role));

    Event::assertDispatched(RoleDeletedEvent::class, fn (RoleDeletedEvent $event): bool => $event->holders->count() === $holderCount);

    expect($queries)->toBeLessThan($holderCount);
});

test('a listener asking the gate after an assignment from the create form finds the holder already granted', function (): void {
    signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'auditor']);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    expect(holds($account, 'view', Post::class))->toBeFalse();

    $seen = [];

    Event::listen(RoleAssignedEvent::class, function () use ($account, &$seen): void {
        $seen[] = holds($account, 'view', Post::class);
    });

    RolesField::assign($account, ['auditor']);

    expect($seen)->toBe([true]);
});

test('a listener asking the gate after an assignment from the tab finds the holder already granted', function (): void {
    signInAsRoleManager();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Models::role()->newQuery()->create(['name' => 'auditor']);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    expect(holds($account, 'view', Post::class))->toBeFalse();

    $seen = [];

    Event::listen(RoleAssignedEvent::class, function () use ($account, &$seen): void {
        $seen[] = holds($account, 'view', Post::class);
    });

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ])->callAction(TestAction::make('assign')->table(), ['role' => 'auditor']);

    expect($seen)->toBe([true]);
});

test('a listener asking the gate after a retraction from the tab finds the holder already stripped', function (): void {
    signInAsRoleManager();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    Bouncer::assign('auditor')->to($account);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    expect(holds($account, 'view', Post::class))->toBeTrue();

    $seen = [];

    Event::listen(RoleRetractedEvent::class, function () use ($account, &$seen): void {
        $seen[] = holds($account, 'view', Post::class);
    });

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ])->callAction(TestAction::make('retract')->table($role));

    expect($seen)->toBe([false]);
});

test('a listener asking the gate after an assignment from the command finds the holder already granted', function (): void {
    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => 'sisa@example.test',
        'password' => 'irrelevant',
    ]);

    Models::role()->newQuery()->create(['name' => 'auditor']);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    expect(holds($account, 'view', Post::class))->toBeFalse();

    $seen = [];

    Event::listen(RoleAssignedEvent::class, function () use ($account, &$seen): void {
        $seen[] = holds($account, 'view', Post::class);
    });

    expect(Artisan::call('filament-bouncer:assign', ['role' => 'auditor', 'user' => 'sisa@example.test']))->toBe(0)
        ->and($seen)->toBe([true]);
});

test('a listener asking the gate after a role deletion finds its former holder already stripped', function (): void {
    signInAsRoleManager();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    $holder = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Bouncer::assign('auditor')->to($holder);
    Bouncer::allow('auditor')->to('view', Post::class);
    Bouncer::refresh();

    expect(holds($holder, 'view', Post::class))->toBeTrue();

    $seen = [];

    Event::listen(RoleDeletedEvent::class, function () use ($holder, &$seen): void {
        $seen[] = holds($holder, 'view', Post::class);
    });

    livewire(ListRoles::class)->callAction(TestAction::make('delete')->table($role));

    expect($seen)->toBe([false]);
});

test('a listener asking the gate after a single row is saved finds the new stance already answered', function (): void {
    signInAsRoleManager();

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'auditor']);

    /** @var Model $ability */
    $ability = Models::ability()->newQuery()->where('name', 'view')->where('entity_type', Post::class)->firstOrFail();

    expect(holds($role, 'view', Post::class))->toBeFalse();

    $seen = [];

    Event::listen(AbilityStanceChangedEvent::class, function () use ($role, &$seen): void {
        $seen[] = holds($role, 'view', Post::class);
    });

    app(RoleAbilities::class)->saveRow($role, $ability, Stance::Granted);

    expect($seen)->toBe([true]);
});

test('the role that holds everything is restored once, and said once', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    Event::fake();

    reconcileStore();

    Event::assertDispatchedTimes(PrivilegedRoleRestoredEvent::class, 1);

    Event::assertDispatched(PrivilegedRoleRestoredEvent::class, fn (PrivilegedRoleRestoredEvent $event): bool => $event->role === 'super-admin' && ! $event->causer instanceof Model);

    Event::fake();

    reconcileStore();

    Event::assertNotDispatched(PrivilegedRoleRestoredEvent::class);
});

test('the role that holds everything is repaired once the wildcard is taken away, and said once', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->where('name', 'super-admin')->firstOrFail();

    Bouncer::disallow('super-admin')->everything();
    Bouncer::refresh();

    expect(holds($role, 'anything-at-all'))->toBeFalse();

    Event::fake();

    reconcileStore();

    Event::assertDispatchedTimes(PrivilegedRoleRestoredEvent::class, 1);

    Event::assertDispatched(PrivilegedRoleRestoredEvent::class, fn (PrivilegedRoleRestoredEvent $event): bool => $event->role === 'super-admin');
});

test('reconciling says how much it wrote and how much it took away', function (): void {
    Event::fake();

    reconcileStore();

    Event::assertDispatched(CatalogReconciledEvent::class, fn (CatalogReconciledEvent $event): bool => $event->written === 20 && $event->pruned === 0 && ! $event->causer instanceof Model);

    Event::fake();

    reconcileStore();

    Event::assertDispatched(CatalogReconciledEvent::class, fn (CatalogReconciledEvent $event): bool => $event->written === 0);

    Bouncer::allow('editor')->to('sing-a-song');

    Event::fake();

    Artisan::call('filament-bouncer:reconcile', ['--prune' => true]);

    Event::assertDispatched(CatalogReconciledEvent::class, fn (CatalogReconciledEvent $event): bool => $event->written === 0 && $event->pruned === 1);
});

test('checking writes nothing, so it says nothing', function (): void {
    Event::fake();

    Artisan::call('filament-bouncer:reconcile', ['--check' => true]);

    Event::assertNotDispatched(CatalogReconciledEvent::class);
    Event::assertNotDispatched(PrivilegedRoleRestoredEvent::class);
});

test('a listener asking the gate after a privileged restore finds the role already reaching everything', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    reconcileStore();

    /** @var Model $role */
    $role = Models::role()->newQuery()->where('name', 'super-admin')->firstOrFail();

    Bouncer::disallow('super-admin')->everything();
    Bouncer::refresh();

    expect(holds($role, 'anything-at-all'))->toBeFalse();

    $seen = [];

    Event::listen(PrivilegedRoleRestoredEvent::class, function () use ($role, &$seen): void {
        $seen[] = holds($role, 'anything-at-all');
    });

    reconcileStore();

    expect($seen)->toBe([true]);
});

test('a listener asking the gate after reconciling finds a pruned ability already stripped from its holder', function (): void {
    Models::role()->newQuery()->create(['name' => 'editor']);
    Bouncer::allow('editor')->to('sing-a-song');
    Bouncer::refresh();

    /** @var Model $role */
    $role = Models::role()->newQuery()->where('name', 'editor')->firstOrFail();

    expect(holds($role, 'sing-a-song'))->toBeTrue();

    $seen = [];

    Event::listen(CatalogReconciledEvent::class, function () use ($role, &$seen): void {
        $seen[] = holds($role, 'sing-a-song');
    });

    Artisan::call('filament-bouncer:reconcile', ['--prune' => true]);

    expect($seen)->toBe([false]);
});

/**
 * The invariant above is a hand-written list of paths, and a list goes stale the moment
 * somebody adds a dispatch without adding its listener test — which is exactly how the
 * privileged restore and the reconcile summary went untested for a fix round. This counts
 * every place `src/` calls the `event()` helper — the dispatch itself, not merely
 * constructing an event object, which a fully-qualified `new \Foo\BarEvent(` or a named
 * constructor could pass through unnoticed — so a new dispatch site changes this number
 * before it can go unnoticed a second time.
 */
function eventDispatchSites(): int
{
    $sites = 0;

    /** @var iterable<SplFileInfo> $files */
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src'));

    foreach ($files as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $source = file_get_contents($file->getPathname());

        $sites += preg_match_all('/\bevent\(/', $source === false ? '' : $source);
    }

    return $sites;
}

test('every place the package dispatches an event is proven by a listener test above, not just a number here', function (): void {
    expect(eventDispatchSites())->toBe(9, 'A new dispatch site means a new listener test in this file, not a bumped number.');
});

/**
 * The count above can be starved rather than grown: a dispatch site that never fires — an
 * event class declared and never announced — leaves every number honest and every listener
 * test above green, because none of them would know to look for it. A wildcard listener
 * catches that the other way around: it watches every path this file exercises and demands
 * the set of classes it actually saw equal every event class this package declares.
 */
test('every event class this package declares is seen at least once by exercising it here', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    /** @var array<class-string, true> $seen */
    $seen = [];

    Event::listen('*', function (string $event) use (&$seen): void {
        if (str_starts_with($event, 'ElPandaPe\\FilamentBouncer\\Events\\')) {
            $seen[$event] = true;
        }
    });

    reconcileStore();

    signInAsRoleManager();

    $account = User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    /** @var Model $auditor */
    $auditor = Models::role()->newQuery()->create(['name' => 'auditor']);

    RolesField::assign($account, ['auditor']);

    Bouncer::refresh();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ])->callAction(TestAction::make('retract')->table($auditor));

    livewire(EditRole::class, ['record' => $auditor->getRouteKey()])
        ->fillForm(['abilities' => [Entity::keyFor(Post::class) => ['view' => Stance::Granted->value]]])
        ->call('save');

    livewire(ListRoles::class)->callAction(TestAction::make('delete')->table($auditor));

    /** @var array<int, string> $files */
    $files = glob(__DIR__.'/../src/Events/*.php') ?: [];

    $declared = collect($files)
        ->map(static fn (string $path): string => 'ElPandaPe\\FilamentBouncer\\Events\\'.basename($path, '.php'))
        ->reject(static fn (string $class): bool => $class === AbilityRef::class)
        ->sort()
        ->values()
        ->all();

    expect(collect($seen)->keys()->sort()->values()->all())->toBe($declared);
});
