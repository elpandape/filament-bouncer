<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\PresentsAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * A rule read in the shape it is changed in.
 *
 * The heading is the reconciliation's answer about this row, and it stands where a delete
 * button would have. That is the trade the screen makes: it will not offer to take a row
 * away, so it owes the reader an account of how the row does go — declared by the code and
 * safe, declared by nothing and due to be swept, or never the reconciliation's to speak
 * for at all.
 */
final class ViewAbility extends ViewRecord
{
    use PresentsAbility;

    protected static string $resource = AbilityResource::class;

    public function getSubheading(): string
    {
        return Declaration::of($this->getRecord())->note();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillFacts($data, $this->getRecord());
    }
}
