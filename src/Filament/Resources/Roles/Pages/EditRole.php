<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Concerns\SavesRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Silber\Bouncer\Database\Models;

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
 * Deleting is not offered here at all: the destructive way lives behind the kebab of the
 * listing, where the approved design keeps it, and its two refusals stand there.
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
     * What the header says about the role, and how far it already reaches.
     *
     * One line of facts on the left, the reach bar on the right — reading how far a role
     * goes is the first thing anybody does before moving a single stance, and the
     * approved design put both in the header for that reason.
     */
    public function getSubheading(): Htmlable
    {
        // The analyser works out `view-string` by looking for the file among the paths the
        // application renders from, and a package's namespaced view is never among them.
        /** @var view-string $view */
        $view = 'filament-bouncer::roles.edit-heading';

        $record = $this->getRecord();

        /** @var string|null $title */
        $title = $record->getAttribute('title');

        /** @var \Carbon\CarbonInterface|null $updated */
        $updated = $record->getAttribute('updated_at');

        return new HtmlString(view($view, [
            'title' => $title,
            'holders' => DB::table(Models::table('assigned_roles'))
                ->where('role_id', $record->getKey())
                ->count(),
            'updated' => $updated,
            'coverage' => RoleCoverage::for($record, app(CatalogRegistry::class)->current()),
        ])->render());
    }

    /**
     * The save lives inside the grid's own summary bar, so the page adds no button row
     * of its own below it.
     */
    public function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema, submitsFromSummary: true);
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getFormActions(): array
    {
        return [];
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
