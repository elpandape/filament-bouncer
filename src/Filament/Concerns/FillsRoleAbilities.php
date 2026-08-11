<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Puts what the role holds into the grid before the page is drawn.
 *
 * The grid is not a column of the role, so nothing arrives in it on its own: Filament
 * fills a form out of the record's attributes and there is no `abilities` attribute to
 * find. The stances have to be read from the store and laid over that data, or every
 * screen opens claiming the role holds nothing.
 *
 * The record is handed in rather than asked for, because the two pages that fill a grid
 * always have one and the page that has none never fills anything: a concern that took
 * it upon itself to ask would have to carry a branch nobody can reach.
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
