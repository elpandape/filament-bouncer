<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\PresentsAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use Filament\Resources\Pages\EditRecord;

/**
 * Changing what a rule says, which is the title and who holds it — and nothing else.
 *
 * There is no delete action, and none is to be added. The resource refuses the answer to
 * every question Filament asks about deleting one of these, so an action put here would
 * be a button that leads to a wall; and a wall is the right end for a request typed at
 * the URL, not for something the screen offered.
 *
 * The stances are written after the record is saved, through the store, so that a cell
 * set here is the same row the roles screen writes — including the refresh, which nothing
 * in Bouncer does on its own and without which the screen repaints the state it just
 * changed.
 */
final class EditAbility extends EditRecord
{
    use PresentsAbility;

    protected static string $resource = AbilityResource::class;

    public function getSubheading(): string
    {
        return Declaration::of($this->getRecord())->note();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillFacts($data, $this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->takeHolders($data);
    }

    protected function afterSave(): void
    {
        $this->writeHolders($this->getRecord());
    }

    protected function getSavedNotificationTitle(): string
    {
        return __('filament-bouncer::abilities.saved');
    }
}
