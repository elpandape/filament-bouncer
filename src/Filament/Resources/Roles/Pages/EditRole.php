<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\EditRecord;

final class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;
}
