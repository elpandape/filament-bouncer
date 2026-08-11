<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables;

use Filament\Tables\Columns\ViewColumn;

/**
 * The reach bar, which is a view of the package's own.
 *
 * It is a class rather than a `ViewColumn` told which view to draw because the column's
 * own property is where a view name belongs: passed as an argument it is an ordinary
 * string, and nothing then checks that the file behind it exists.
 */
final class CoverageColumn extends ViewColumn
{
    protected string $view = 'filament-bouncer::tables.coverage-bar';
}
