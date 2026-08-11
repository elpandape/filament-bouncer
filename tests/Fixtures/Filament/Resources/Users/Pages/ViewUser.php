<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users\Pages;

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;
}
