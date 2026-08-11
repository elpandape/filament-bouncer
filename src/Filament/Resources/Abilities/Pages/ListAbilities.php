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
     * The one thing that may be composed here is a narrowed rule, and the button says so
     * rather than saying "new": everything else on this screen was written by the
     * reconciliation and cannot be written from a browser at all.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('filament-bouncer::abilities.narrow')),
        ];
    }
}
