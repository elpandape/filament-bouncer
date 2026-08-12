<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityHolders;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

test('a form with no record asks the catalogue nothing', function (): void {
    app()->forgetInstance(CatalogRegistry::class);

    app()->bind(CatalogRegistry::class, static function (): CatalogRegistry {
        throw new RuntimeException('The catalogue was walked for a form with no record.');
    });

    expect(AbilityHolders::make('holders')->getRows())->toBeEmpty()
        ->and(AbilityHolders::make('holders')->isWithheld())->toBeFalse();
});

test('the field brings the words its cells are read in', function (): void {
    expect(AbilityHolders::make('holders')->getStances())
        ->toHaveKeys([Stance::Granted->value, Stance::Neutral->value, Stance::Forbidden->value])
        ->and(AbilityHolders::make('holders')->getNeutral())->toBe(Stance::Neutral->value);
});

test('a form with no record has no direct holders to speak of', function (): void {
    expect(AbilityHolders::make('holders')->getDirectUsers())->toBeEmpty();
});
