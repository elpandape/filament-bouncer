<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Reads the role's grants into the grid, which no attribute of the record can supply.
 */
trait FillsRoleAbilities
{
    abstract public function getRecord(): Model;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data[RoleForm::ABILITIES] = app(RoleAbilities::class)->toFormState($this->getRecord());

        return $data;
    }
}
