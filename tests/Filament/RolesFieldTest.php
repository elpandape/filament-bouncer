<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Forms\RolesField;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\RolesFieldHost;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function fieldRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function fieldAccount(): User
{
    return User::forceCreate([
        'name' => 'Sisa',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);
}

function fieldHolds(Model $account, string $role): bool
{
    return Models::role()->newQuery()
        ->join(
            Models::table('assigned_roles'),
            Models::table('assigned_roles').'.role_id',
            '=',
            Models::table('roles').'.id',
        )
        ->where(Models::table('roles').'.name', $role)
        ->where(Models::table('assigned_roles').'.entity_id', $account->getKey())
        ->where(Models::table('assigned_roles').'.entity_type', $account->getMorphClass())
        ->exists();
}

test('the field offers every role there is', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    fieldRole();
    fieldRole('super-admin');

    expect(RolesField::make()->getOptions())->toHaveKeys(['editor', 'super-admin']);
});

test('the way back in is offered locked to somebody who does not hold it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    fieldRole();
    fieldRole('super-admin');

    $field = RolesField::make();

    expect($field->isOptionDisabled('super-admin', 'super-admin'))->toBeTrue()
        ->and($field->isOptionDisabled('editor', 'editor'))->toBeFalse();
});

test('a request naming a role the screen would not offer writes nothing', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    fieldRole('super-admin');

    $account = fieldAccount();

    RolesField::assign($account, ['super-admin', 'a-role-nobody-composed']);

    expect(fieldHolds($account, 'super-admin'))->toBeFalse()
        ->and(Models::role()->newQuery()->where('name', 'a-role-nobody-composed')->exists())->toBeFalse();
});

test('somebody holding the way back in hands it on', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $editor = signInAsRoleManager();

    fieldRole('super-admin');
    Bouncer::assign('super-admin')->to($editor);
    Bouncer::refresh();

    $account = fieldAccount();

    RolesField::assign($account, ['super-admin']);

    expect(fieldHolds($account, 'super-admin'))->toBeTrue();
});

test('nothing ticked writes nothing at all', function (): void {
    signInAsRoleManager();

    fieldRole();

    $account = fieldAccount();

    RolesField::assign($account, []);

    expect(DB::table(Models::table('assigned_roles'))->count())->toBe(0);
});

test('an ordinary role is handed on, and the account reads it back at once', function (): void {
    signInAsRoleManager();

    fieldRole();

    $account = fieldAccount();

    expect($account->isAn('editor'))->toBeFalse();

    RolesField::assign($account, ['editor']);

    expect($account->isAn('editor'))->toBeTrue();
});

test('the field keeps its state out of the attributes the account is written from', function (): void {
    signInAsRoleManager();

    expect(RolesField::make()->isDehydrated())->toBeFalse()
        ->and(RolesField::make()->getName())->toBe(RolesField::NAME);
});

test('the field is offered on the form that creates an account', function (): void {
    signInAsRoleManager();

    fieldRole();

    livewire(RolesFieldHost::class, ['operation' => 'create'])
        ->assertSee('Roles')
        ->assertSee('editor');
});

test('the field is not offered where ticking it would write nothing', function (): void {
    signInAsRoleManager();

    fieldRole();

    livewire(RolesFieldHost::class, ['operation' => 'edit'])
        ->assertDontSee('editor')
        ->assertSee('wire:id');
});

test('a form that wants the field anyway says so and gets it', function (): void {
    signInAsRoleManager();

    fieldRole();

    livewire(RolesFieldHost::class, ['operation' => 'edit', 'wantedAnyway' => true])
        ->assertSee('editor');
});
