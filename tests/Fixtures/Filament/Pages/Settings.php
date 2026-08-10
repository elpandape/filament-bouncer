<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesPage;
use Filament\Pages\Page;

final class Settings extends Page
{
    use AuthorizesPage;
}
