<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

function labels(): Labels
{
    return app(Labels::class);
}

test('an action nobody has a word for reads as its method name, made readable', function (): void {
    expect(labels()->action('approveInvoice'))->toBe('Approve Invoice');
});

test('the package has a word for the actions Filament asks about', function (): void {
    expect(labels()->action('viewAny'))->toBe('See the list')
        ->and(labels()->action('forceDelete'))->toBe('Delete for good');
});

test('what the application configured beats what the package translates', function (): void {
    config()->set('filament-bouncer.labels.actions.viewAny', 'Browse');

    expect(labels()->action('viewAny'))->toBe('Browse');
});

test('the scopes and the stances go through the same three sources', function (): void {
    config()->set('filament-bouncer.labels.stances.forbidden', 'Never');

    expect(labels()->scope(AbilityScope::Irreversible))->toBe('Beyond undoing')
        ->and(labels()->stance(Stance::Granted))->toBe('Granted')
        ->and(labels()->stance(Stance::Forbidden))->toBe('Never');
});

test('the three stances come back ready to hand to a set of buttons', function (): void {
    expect(labels()->stances())->toBe([
        'granted' => 'Granted',
        'neutral' => 'Not granted',
        'forbidden' => 'Forbidden',
    ]);
});

test('Spanish ships with the package', function (): void {
    app()->setLocale('es');

    expect(labels()->action('viewAny'))->toBe('Ver el listado')
        ->and(labels()->scope(AbilityScope::Withdraw))->toBe('Retirada')
        ->and(labels()->stance(Stance::Forbidden))->toBe('Prohibida');
});
