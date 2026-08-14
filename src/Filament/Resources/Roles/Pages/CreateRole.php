<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\SavesRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Composing a role: naming it and saying what it may do, on one screen.
 *
 * One screen and not a wizard: composing is the same screen as editing, and two ways of composing
 * the same thing is one more than anybody needs to learn. What goes with the steps is the review
 * before abilities are handed out — taken knowingly, since the matrix shows at once what the steps
 * made people walk through.
 */
final class CreateRole extends CreateRecord
{
    use SavesRoleAbilities;

    protected static string $resource = RoleResource::class;

    /**
     * The grid refuses a state saying nothing about anything, which only makes sense here:
     * a role granting nothing and forbidding nothing answers no question, and there is
     * nothing about it worth writing down. Clearing one back to neutral is how what an
     * existing role holds is taken away, so the edit screen asks for no such thing.
     */
    public function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema, requiresAStance: true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->takeStances($data);
    }

    /**
     * There is nothing to grant abilities to until the record has been saved, which is
     * why the stances are written here and not along with it.
     */
    protected function afterCreate(): void
    {
        $role = $this->getRecord();

        if ($role instanceof Model) {
            $this->writeStances($role);
        }
    }
}
