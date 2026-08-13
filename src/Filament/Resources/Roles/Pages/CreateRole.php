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
 * It used to be three steps, and they were the answer to a catalogue laid out as a card
 * per subject with a row per action — a page long enough that a name field above it read
 * as an afterthought. Drawn as a matrix the whole catalogue fits, and the alta is the same
 * screen as the edit: two ways of composing the same thing is one more than anybody needs
 * to learn.
 *
 * What went with the steps was the review, the last screen before abilities were handed
 * out. That is a real loss and it is taken knowingly: the matrix shows at once what the
 * list made people walk through, and the role's record page reads it back the moment it
 * exists.
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
