<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;

/**
 * Splits the grid off the role, because the grid is not a column on it.
 */
trait SavesRoleAbilities
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withoutAbilities(array $data): array
    {
        unset($data[RoleForm::ABILITIES]);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, bool>>
     */
    protected function abilitiesFrom(array $data): array
    {
        /** @var array<string, array<string, bool>> $abilities */
        $abilities = $data[RoleForm::ABILITIES] ?? [];

        return $abilities;
    }
}
