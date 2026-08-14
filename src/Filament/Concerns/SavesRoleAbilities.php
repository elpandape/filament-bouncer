<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Takes the grid out of the data, and writes it once there is a record to write it to.
 *
 * Leaving the grid's state in the data hands a key nobody can fill to a mass assignment: the role
 * model declares `name` and `title` fillable and nothing else, and Eloquent run strictly throws
 * rather than dropping it.
 *
 * The stances can only be written afterwards, because a creation screen has no record to write
 * them against until it has been saved.
 */
trait SavesRoleAbilities
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $stances = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function takeStances(array $data): array
    {
        /** @var array<string, array<string, string>> $stances */
        $stances = is_array($data[RoleForm::ABILITIES] ?? null) ? $data[RoleForm::ABILITIES] : [];

        $this->stances = $stances;

        unset($data[RoleForm::ABILITIES]);

        return $data;
    }

    /**
     * Nothing is walked here beyond handing the state over.
     *
     * The store drives everything off the catalogue, so a cell smuggled into the request
     * for something the panel does not declare has nothing to match against, and a cell
     * the grid never offered is left exactly as the role already had it.
     */
    protected function writeStances(Model $role): void
    {
        app(RoleAbilities::class)->save($role, $this->stances);
    }
}
