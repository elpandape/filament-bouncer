<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages;

use Filament\Pages\Page;

/**
 * A page that decides nothing, and is therefore open to everybody who reaches the panel.
 * It is deliberately left off the fixture panel, so that only the guard's own tests meet it.
 */
final class Unguarded extends Page {}
