<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Concerns\SavesRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

/**
 * Changing what a role says.
 *
 * The two refusals the resource makes are asked here before anything is drawn, and that
 * is deliberate rather than incidental: `EditRecord::mount()` and `hydrate()` both call
 * `authorizeAccess()`, which aborts unless `RoleResource::canEdit()` says yes. A request
 * typed straight at the URL of your own role, or of the way back in, meets the same wall
 * the hidden button does — hiding the button alone would be theatre, since the page is
 * one address away.
 *
 * Deleting is not offered here at all: the destructive way lives behind the kebab of the
 * listing, and its two refusals stand there.
 *
 * The grid is filled and saved by the two concerns, because it is not a column of the
 * role and Filament has no way of knowing that.
 */
final class EditRole extends EditRecord
{
    use FillsRoleAbilities;
    use SavesRoleAbilities;

    protected static string $resource = RoleResource::class;

    public function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillStances($data, $this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->takeStances($data);
    }

    protected function afterSave(): void
    {
        $this->writeStances($this->getRecord());
    }
}
