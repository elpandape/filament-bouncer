<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Stats;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

test('a page is out of reach for somebody who was never granted it', function (): void {
    signIn();

    expect(Settings::canAccess())->toBeFalse();
});

test('a page opens for somebody who holds its ability', function (): void {
    grant(signIn(), [['page:'.Subject::keyFor(Settings::class), null]]);

    expect(Settings::canAccess())->toBeTrue();
});

test('a widget is out of sight for somebody who was never granted it', function (): void {
    signIn();

    expect(Stats::canView())->toBeFalse();
});

test('a widget appears for somebody who holds its ability', function (): void {
    grant(signIn(), [['widget:'.Subject::keyFor(Stats::class), null]]);

    expect(Stats::canView())->toBeTrue();
});

test('nobody signed in reaches nothing', function (): void {
    expect(Settings::canAccess())->toBeFalse()
        ->and(Stats::canView())->toBeFalse();
});

test('a component the catalogue was told to leave out is open to everybody', function (): void {
    config()->set('filament-bouncer.ignore', [Settings::class, Stats::class]);
    app(CatalogRegistry::class)->forget();

    expect(Settings::canAccess())->toBeTrue()
        ->and(Stats::canView())->toBeTrue();
});
