<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

test('with no privileged role named, everything may be handed on and nothing is a last holder', function (): void {
    config()->set('filament-bouncer.privileged_role');

    $privileged = resolve(PrivilegedRole::class);

    expect($privileged->mayBeHandedOutBy(null))->toBeTrue()
        ->and($privileged->isLastHolder(signIn()))->toBeFalse();
});

test('a privileged role that was never created has no last holder', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    expect(resolve(PrivilegedRole::class)->isLastHolder(signIn()))->toBeFalse();
});

test('a privileged role nobody holds has no last holder either', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    Models::role()->newQuery()->create(['name' => 'super-admin']);

    expect(resolve(PrivilegedRole::class)->isLastHolder(signIn()))->toBeFalse();
});
