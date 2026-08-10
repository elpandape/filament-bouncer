<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsAbilityHolders;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use Filament\Resources\Pages\EditRecord;

final class EditAbility extends EditRecord
{
    use FillsAbilityHolders;

    protected static string $resource = AbilityResource::class;
}
