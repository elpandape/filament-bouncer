<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Forms\RolesField;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function account(string $email = 'amaru@example.test'): User
{
    return User::query()->forceCreate(['name' => 'Amaru', 'email' => $email, 'password' => 'secret']);
}

test('the field offers every role there is', function (): void {
    signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'editor']);
    Models::role()->newQuery()->create(['name' => 'reviewer']);

    expect(RolesField::make()->getOptions())->toBe(['editor' => 'editor', 'reviewer' => 'reviewer']);
});

test('the privileged role is offered disabled to somebody who does not hold it', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'super-admin']);
    Models::role()->newQuery()->create(['name' => 'editor']);

    $field = RolesField::make();

    expect($field->getOptions())->toHaveKey('super-admin')
        ->and($field->isOptionDisabled('super-admin', 'super-admin'))->toBeTrue()
        ->and($field->isOptionDisabled('editor', 'editor'))->toBeFalse();
});

test('a request naming a role the screen would not offer is refused', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'super-admin']);
    Models::role()->newQuery()->create(['name' => 'editor']);

    $record = account();
    RolesField::assign($record, ['editor', 'super-admin']);

    expect($record->fresh()?->isAn('editor'))->toBeTrue()
        ->and($record->fresh()?->isAn('super-admin'))->toBeFalse();
});

test('somebody who holds the privileged role hands it on', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    $editor = signInAsRoleManager();

    Models::role()->newQuery()->create(['name' => 'super-admin']);
    $editor->assign('super-admin');
    Bouncer::refresh();

    $record = account();
    RolesField::assign($record, ['super-admin']);

    expect($record->fresh()?->isAn('super-admin'))->toBeTrue();
});

test('nothing ticked writes nothing', function (): void {
    signInAsRoleManager();

    $record = account();
    RolesField::assign($record, []);

    expect($record->fresh()?->roles()->count())->toBe(0);
});
