<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAbilities extends ListRecords
{
    protected static string $resource = AbilityResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
