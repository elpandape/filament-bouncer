<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets;

use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesWidget;
use Filament\Widgets\Widget;

/**
 * Registered through `make()`, so the panel hands it over wrapped in a configuration
 * object rather than as a plain class name.
 */
final class Activity extends Widget
{
    use AuthorizesWidget;

    protected string $view = 'filament-widgets::stats-overview-widget';
}
