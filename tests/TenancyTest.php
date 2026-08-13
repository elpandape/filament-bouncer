<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Support\Tenancy;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

function tenancy(): Tenancy
{
    return resolve(Tenancy::class);
}

test('takes the answer from configuration where the application gave one', function (): void {
    config()->set('filament-bouncer.tenancy', true);

    expect(tenancy()->inUse())->toBeTrue();

    config()->set('filament-bouncer.tenancy', false);

    expect(tenancy()->inUse())->toBeFalse();
});

test('asks the panel where configuration says nothing', function (): void {
    config()->set('filament-bouncer.tenancy');

    expect(tenancy()->inUse())->toBeFalse();
});

/**
 * The panel is read while screens are built, so a misconfigured id must not take a screen down over
 * a question whose honest answer, when it cannot be asked, is "no tenancy".
 */
test('answers no rather than throwing when the panel cannot even be found', function (): void {
    config()->set('filament-bouncer.tenancy');
    config()->set('filament-bouncer.panel', 'a-panel-that-is-not-there');

    expect(tenancy()->inUse())->toBeFalse();
});
