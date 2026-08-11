<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Widgets\RoleStats;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

final class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [RoleStats::class];
    }
}
