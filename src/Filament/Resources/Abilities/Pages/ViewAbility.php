<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsAbilityHolders;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAbility extends ViewRecord
{
    use FillsAbilityHolders;

    protected static string $resource = AbilityResource::class;
}
