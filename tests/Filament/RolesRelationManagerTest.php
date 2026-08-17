<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\RelationManagers\RolesRelationManager;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users\Pages\ViewUser;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function tabbedRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function tabbedAccount(): User
{
    return User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);
}

/**
 * @return Testable<Component>
 */
function rolesTab(Model $account): Testable
{
    return livewire(RolesRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => ViewUser::class,
    ]);
}

function tabbedManager(Model $account): RolesRelationManager
{
    /** @var RolesRelationManager $manager */
    $manager = rolesTab($account)->instance();

    return $manager;
}

function retractArmedByHand(Model $account, Model $role): void
{
    /** @var int|string $key */
    $key = $role->getKey();

    rolesTab($account)
        ->call('mountAction', 'retract', [], ['table' => true, 'recordKey' => (string) $key])
        ->call('callMountedAction');
}

test('the tab lists the roles the account holds', function (): void {
    signInAsRoleManager();

    $account = tabbedAccount();

    $editor = tabbedRole();
    $reviewer = tabbedRole('reviewer');
    $unrelated = tabbedRole('auditor');

    Bouncer::assign(new Collection(['editor', 'reviewer']))->to($account);
    Bouncer::refresh();

    rolesTab($account)
        ->assertCanSeeTableRecords([$editor, $reviewer])
        ->assertCanNotSeeTableRecords([$unrelated]);
});

test('assigning writes, and the same request reads it back', function (): void {
    signInAsRoleManager();

    $account = tabbedAccount();
    $editor = tabbedRole();

    expect($account->isAn('editor'))->toBeFalse();

    rolesTab($account)->callAction(TestAction::make('assign')->table(), ['role' => 'editor']);

    expect($account->isAn('editor'))->toBeTrue();

    rolesTab($account)->assertCanSeeTableRecords([$editor]);
});

test('taking a role away writes too', function (): void {
    signInAsRoleManager();

    $account = tabbedAccount();
    $editor = tabbedRole();

    Bouncer::assign('editor')->to($account);
    Bouncer::refresh();

    expect($account->isAn('editor'))->toBeTrue();

    rolesTab($account)->callAction(TestAction::make('retract')->table($editor));

    expect($account->isAn('editor'))->toBeFalse();
});

test('the way back in is offered by nobody who does not hold it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    tabbedRole();
    tabbedRole('super-admin');

    $assignable = tabbedManager(tabbedAccount())->assignable();

    expect($assignable)->toHaveKey('editor')
        ->and($assignable)->not->toHaveKey('super-admin');
});

test('somebody holding the way back in offers it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $editor = signInAsRoleManager();

    tabbedRole('super-admin');
    Bouncer::assign('super-admin')->to($editor);
    Bouncer::refresh();

    expect(tabbedManager(tabbedAccount())->assignable())->toHaveKey('super-admin');
});

test('an assignment armed by hand hands out nothing the tab would not offer', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $account = tabbedAccount();
    tabbedRole('super-admin');

    rolesTab($account)
        ->call('mountAction', 'assign', [], ['table' => true])
        ->set('mountedActions.0.data.role', 'super-admin')
        ->call('callMountedAction');

    expect($account->isAn('super-admin'))->toBeFalse();
});

test('nobody takes the way back in off its last holder', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $account = tabbedAccount();
    $privileged = tabbedRole('super-admin');

    Bouncer::assign('super-admin')->to($account);
    Bouncer::refresh();

    rolesTab($account)->assertActionHidden(TestAction::make('retract')->table($privileged));

    retractArmedByHand($account, $privileged);

    expect($account->isAn('super-admin'))->toBeTrue();
});

test('the way back in comes off an account that is not its last holder', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $keeper = signInAsRoleManager();

    $account = tabbedAccount();
    $privileged = tabbedRole('super-admin');

    Bouncer::assign('super-admin')->to($account);
    Bouncer::assign('super-admin')->to($keeper);
    Bouncer::refresh();

    rolesTab($account)->callAction(TestAction::make('retract')->table($privileged));

    expect($account->isAn('super-admin'))->toBeFalse();
});

test('the tab is called what it holds', function (): void {
    signInAsRoleManager();

    rolesTab(tabbedAccount())
        ->assertSee(__('filament-bouncer::roles.relation.title'))
        ->assertSee(__('filament-bouncer::roles.relation.empty'));
});

test('a row on the tab opens the role it names', function (): void {
    signInAsRoleManager();

    $account = tabbedAccount();
    $editor = tabbedRole();

    Bouncer::assign('editor')->to($account);
    Bouncer::refresh();

    // The row says the name and the title and nothing about what the role may do, so without this
    // reading that means going to the roles listing and finding the row again by hand.
    rolesTab($account)
        ->assertActionExists(
            TestAction::make('view')->table($editor),
            checkActionUsing: fn (Action $action): bool => $action->getUrl() === RoleResource::getUrl('view', ['record' => $editor]),
        );
});

test('the tab carries no icon until an application names one', function (): void {
    $account = tabbedAccount();

    expect(RolesRelationManager::getIcon($account, ViewUser::class))->toBeNull();

    config()->set('filament-bouncer.relation.icon', 'heroicon-o-shield-check');

    expect(RolesRelationManager::getIcon($account, ViewUser::class))->toBe('heroicon-o-shield-check');
});
