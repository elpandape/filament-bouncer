<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Exceptions\UnguardedComponent;
use ElPandaPe\FilamentBouncer\Filament\FilamentBouncerPlugin;
use ElPandaPe\FilamentBouncer\Filament\PanelGuard;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Unguarded as UnguardedPage;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\CommentResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\PostResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Stats;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Unguarded as UnguardedWidget;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;

pest()->extend(TestCase::class);

function guarded(): PanelGuard
{
    return app(PanelGuard::class);
}

test('a page that decides nothing stops the panel booting', function (): void {
    $panel = Panel::make()->id('unguarded')->pages([UnguardedPage::class]);

    expect(static function () use ($panel): void {
        guarded()->check($panel);
    })
        ->toThrow(UnguardedComponent::class, 'does not decide who may reach it');
});

test('a widget that decides nothing stops the panel booting', function (): void {
    $panel = Panel::make()->id('unguarded')->widgets([UnguardedWidget::class]);

    expect(static function () use ($panel): void {
        guarded()->check($panel);
    })
        ->toThrow(UnguardedComponent::class, 'does not decide who may see it');
});

test('a page and a widget that do decide are let through', function (): void {
    guarded()->check(Filament::getDefaultPanel());

    expect([Settings::class, Stats::class])->toHaveCount(2);
});

test('a component named in the ignore list is let through undecided', function (): void {
    config()->set('filament-bouncer.ignore', [UnguardedPage::class, UnguardedWidget::class]);

    $panel = Panel::make()->id('unguarded')
        ->pages([UnguardedPage::class])
        ->widgets([UnguardedWidget::class]);

    guarded()->check($panel);

    expect(config('filament-bouncer.ignore'))->toHaveCount(2);
});

test('the plugin runs the guard when the panel boots', function (): void {
    $panel = Panel::make()->id('unguarded')->pages([UnguardedPage::class]);

    expect(static function () use ($panel): void {
        FilamentBouncerPlugin::make()->boot($panel);
    })
        ->toThrow(UnguardedComponent::class);
});

test('it names the resources whose model has no policy', function (): void {
    expect(guarded()->openResources(Filament::getDefaultPanel()))
        ->toBe([CommentResource::class]);
});

test('an ignored resource is not named', function (): void {
    config()->set('filament-bouncer.ignore', [CommentResource::class]);

    expect(guarded()->openResources(Filament::getDefaultPanel()))->toBeEmpty();
});

test('a resource whose model has a policy is not named', function (): void {
    expect(guarded()->openResources(Filament::getDefaultPanel()))
        ->not->toContain(PostResource::class);
});
