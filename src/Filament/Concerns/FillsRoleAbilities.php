<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Puts what the role holds into the grid before the page is drawn.
 *
 * The grid is not a column of the role, so Filament finds no `abilities` attribute to fill it
 * with: the stances are read from the store and laid over the data, or every screen opens
 * claiming the role holds nothing.
 *
 * The record is handed in rather than asked for, since a concern that asked would carry a branch
 * nobody can reach.
 */
trait FillsRoleAbilities
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillStances(array $data, Model $role): array
    {
        $data[RoleForm::ABILITIES] = app(RoleAbilities::class)->toFormState($role);

        return $data;
    }
}
