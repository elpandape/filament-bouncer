<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\FilamentBouncerServiceProvider;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

test('the service provider is registered by the package discovery', function (): void {
    expect(app()->getProviders(FilamentBouncerServiceProvider::class))->not->toBeEmpty();
});

test('the packaged configuration is merged under its own key', function (): void {
    expect(config('filament-bouncer.navigation.slug'))->toBe('security/roles');
});

test('bouncer is installed and its tables are migrated', function (): void {
    expect(Illuminate\Support\Facades\Schema::hasTable('abilities'))->toBeTrue()
        ->and(Illuminate\Support\Facades\Schema::hasTable('roles'))->toBeTrue();
});
