<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Concerns\SavesRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

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
 * The grid is filled and saved by the two concerns, because it is not a column of the
 * role and Filament has no way of knowing that.
 */
final class EditRole extends EditRecord
{
    use FillsRoleAbilities;
    use SavesRoleAbilities;

    protected static string $resource = RoleResource::class;

    /**
     * @return array<int, Action>
     */
    /**
     * The same reach bar the record page carries, above the form that changes it.
     *
     * Reading how far a role already goes is the first thing anybody does before moving
     * a single stance, and the approved design put it here for that reason.
     */
    public function getSubheading(): Htmlable
    {
        // The analyser works out `view-string` by looking for the file among the paths the
        // application renders from, and a package's namespaced view is never among them.
        /** @var view-string $view */
        $view = 'filament-bouncer::roles.coverage';

        return new HtmlString(view($view, [
            'coverage' => RoleCoverage::for($this->getRecord(), app(CatalogRegistry::class)->current()),
        ])->render());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => RoleResource::canDelete($this->getRecord())),
        ];
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
