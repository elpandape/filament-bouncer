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
     * The button says what it makes, because «New ability» would be a promise the screen
     * does not keep: a plain ability comes from the code that asks about it, and this
     * makes the narrowed one instead.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-bouncer::abilities.narrow')),
        ];
    }
}
