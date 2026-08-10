<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\FilamentBouncerServiceProvider;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Database\Models;

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

test('nobody owns a role, which is what keeps a strict application alive', function (): void {
    $user = signIn();
    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    expect(static function () use ($user, $role): void {
        app(Bouncer::class)->getClipboard()->check($user, 'view', $role);
    })->not->toThrow(MissingAttributeException::class);
});

test('the single argument form of ownedVia cannot be called at all', function (): void {
    expect(static function (): void {
        Models::ownedVia(static fn (): bool => false);
    })->toThrow(TypeError::class);
});
