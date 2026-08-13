<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Actions\ProbeAbility;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewAbility extends ViewRecord
{
    protected static string $resource = AbilityResource::class;

    /**
     * The reconciliation's answer about this row stands where a delete button would have. That is
     * the trade the screen makes: it will not offer to take a row away, so it owes the reader an
     * account of how the row does go.
     */
    public function getSubheading(): string
    {
        return Declaration::of($this->getRecord())->note();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ProbeAbility::make(),
            EditAction::make(),
        ];
    }
}
