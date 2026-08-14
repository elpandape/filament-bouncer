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
 * `EditRecord::mount()` and `hydrate()` both call `authorizeAccess()`, so the resource's two
 * refusals are asked before anything is drawn: a URL typed by hand meets the same wall the hidden
 * button does.
 *
 * The grid is filled and saved by the two concerns, because it is not a column of the role and
 * Filament has no way of knowing that.
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
