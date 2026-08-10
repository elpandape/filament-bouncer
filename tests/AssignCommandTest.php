<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function assign(string $role, string $user): int
{
    return Artisan::call('filament-bouncer:assign', ['role' => $role, 'user' => $user]);
}

test('it hands a role to somebody found by their email address', function (): void {
    $user = signIn();
    Models::role()->newQuery()->create(['name' => 'owner']);

    /** @var string $email */
    $email = $user->getAttribute('email');

    expect(assign('owner', $email))->toBe(0)
        ->and(Bouncer::is($user)->an('owner'))->toBeTrue();
});

test('it hands a role to somebody found by their key', function (): void {
    $user = signIn();
    Models::role()->newQuery()->create(['name' => 'owner']);

    /** @var int $key */
    $key = $user->getKey();

    expect(assign('owner', (string) $key))->toBe(0)
        ->and(Bouncer::is($user)->an('owner'))->toBeTrue();
});

test('it refuses a role that does not exist, rather than inventing one', function (): void {
    $user = signIn();

    /** @var string $email */
    $email = $user->getAttribute('email');

    expect(assign('ownr', $email))->toBe(1)
        ->and(Artisan::output())->toContain('There is no role called [ownr]')
        ->and(Models::role()->newQuery()->count())->toBe(0);
});

test('it refuses a user nobody answers to', function (): void {
    Models::role()->newQuery()->create(['name' => 'owner']);

    expect(assign('owner', 'nobody@example.test'))->toBe(1)
        ->and(Artisan::output())->toContain('No user answers to');
});
