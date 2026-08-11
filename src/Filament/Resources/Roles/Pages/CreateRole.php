<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
