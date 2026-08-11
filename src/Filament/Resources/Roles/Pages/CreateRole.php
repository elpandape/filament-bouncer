<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\SavesRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;

/**
 * Composing a role, one question at a time.
 *
 * Three steps rather than one page, because the middle one is the whole catalogue and a
 * name field above it reads as an afterthought. Naming, choosing and reading back what
 * is about to be written are three different jobs, and the last of them is the point:
 * handing out abilities is the sort of thing that should be read once before it is done.
 *
 * The review step is drawn from the form state and not from the store, because there is
 * nothing in the store yet.
 */
final class CreateRole extends CreateRecord
{
    use HasWizard;
    use SavesRoleAbilities;

    protected static string $resource = RoleResource::class;

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return [
            Step::make(__('filament-bouncer::roles.wizard.identity'))
                ->description(__('filament-bouncer::roles.wizard.identity_hint'))
                ->schema(RoleForm::identity())
                ->columns(2),
            Step::make(__('filament-bouncer::roles.wizard.abilities'))
                ->description(__('filament-bouncer::roles.wizard.abilities_hint'))
                ->schema([RoleForm::grid()]),
            Step::make(__('filament-bouncer::roles.wizard.review'))
                ->description(__('filament-bouncer::roles.wizard.review_hint'))
                ->schema([Text::make(RoleForm::review())]),
        ];
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
