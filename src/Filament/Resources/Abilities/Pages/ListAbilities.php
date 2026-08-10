<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use Filament\Resources\Pages\ListRecords;

final class ListAbilities extends ListRecords
{
    protected static string $resource = AbilityResource::class;
}
