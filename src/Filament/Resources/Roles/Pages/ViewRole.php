<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * The same grid the edit screen shows, rendered disabled by Filament, so that what a
 * role holds is read in exactly the shape in which it is changed.
 */
final class ViewRole extends ViewRecord
{
    use FillsRoleAbilities;

    protected static string $resource = RoleResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            // Hidden rather than left to fail, because Filament does not ask the resource
            // before drawing it: on the role you hold yourself, and on the one that holds
            // everything, the button would lead straight to a refusal.
            EditAction::make()
                ->visible(fn (): bool => RoleResource::canEdit($this->getRecord())),
        ];
    }
}
