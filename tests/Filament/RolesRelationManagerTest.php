<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\RelationManagers\RolesRelationManager;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users\Pages\ViewUser;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

/** @return Livewire\Features\SupportTesting\Testable<Livewire\Component> */
function tabOf(User $owner): Livewire\Features\SupportTesting\Testable
{
    return livewire(RolesRelationManager::class, [
        'ownerRecord' => $owner,
        'pageClass' => ViewUser::class,
    ]);
}

test('the tab lists the roles the account holds', function (): void {
    signInAsRoleManager();

    $owner = User::query()->forceCreate(['name' => 'Amaru', 'email' => 'amaru@example.test', 'password' => 'secret']);
    Models::role()->newQuery()->create(['name' => 'editor']);
    $owner->assign('editor');
    Bouncer::refresh();

    tabOf($owner)->assertCanSeeTableRecords($owner->roles)->assertSee('editor')->assertOk();
});

test('assigning from the tab writes the role and is read back within the request', function (): void {
    signInAsRoleManager();

    $owner = User::query()->forceCreate(['name' => 'Amaru', 'email' => 'amaru@example.test', 'password' => 'secret']);
    Models::role()->newQuery()->create(['name' => 'editor']);

    tabOf($owner)->callAction(TestAction::make('assign')->table(), ['role' => 'editor']);

    expect($owner->fresh()?->isAn('editor'))->toBeTrue();
});

test('taking a role away from the tab writes it too', function (): void {
    signInAsRoleManager();

    $owner = User::query()->forceCreate(['name' => 'Amaru', 'email' => 'amaru@example.test', 'password' => 'secret']);
    Models::role()->newQuery()->create(['name' => 'editor']);
    $owner->assign('editor');
    Bouncer::refresh();

    $role = Models::role()->newQuery()->where('name', 'editor')->sole();

    tabOf($owner)->callAction(TestAction::make('retract')->table($role));

    expect($owner->fresh()?->isAn('editor'))->toBeFalse();
});

test('the privileged role is not offered to somebody who does not hold it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    signInAsRoleManager();

    $owner = User::query()->forceCreate(['name' => 'Amaru', 'email' => 'amaru@example.test', 'password' => 'secret']);
    Models::role()->newQuery()->create(['name' => 'super-admin']);
    Models::role()->newQuery()->create(['name' => 'editor']);

    tabOf($owner)->callAction(TestAction::make('assign')->table(), ['role' => 'super-admin']);

    expect($owner->fresh()?->isAn('super-admin'))->toBeFalse();
});

test('the privileged role is offered by somebody who holds it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    $editor = signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'super-admin']);
    $editor->assign('super-admin');
    Bouncer::refresh();

    $owner = User::query()->forceCreate(['name' => 'Amaru', 'email' => 'amaru@example.test', 'password' => 'secret']);

    tabOf($owner)->callAction(TestAction::make('assign')->table(), ['role' => 'super-admin']);

    expect($owner->fresh()?->isAn('super-admin'))->toBeTrue();
});

test('the last holder of the privileged role keeps it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    $editor = signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'super-admin']);
    $editor->assign('super-admin');
    Bouncer::refresh();

    $role = Models::role()->newQuery()->where('name', 'super-admin')->sole();

    tabOf($editor)->assertActionHidden(TestAction::make('retract')->table($role));

    expect($editor->fresh()?->isAn('super-admin'))->toBeTrue();
});
