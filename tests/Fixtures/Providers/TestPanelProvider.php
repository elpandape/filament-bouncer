<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Providers;

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\CommentResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\PostResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Activity;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Stats;
use Filament\Panel;
use Filament\PanelProvider;

final class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('test')
            ->path('test')
            ->default()
            ->resources([
                PostResource::class,
                CommentResource::class,
            ])
            ->pages([
                Settings::class,
            ])
            ->widgets([
                Stats::class,
                Activity::make(),
            ]);
    }
}
