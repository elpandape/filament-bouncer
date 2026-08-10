<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets;

use Filament\Widgets\Widget;

final class Unguarded extends Widget
{
    protected string $view = 'filament-widgets::stats-overview-widget';
}
