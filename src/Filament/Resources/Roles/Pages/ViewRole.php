<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * What a role says, read in the shape it is written in.
 *
 * The same grid as the edit screen, disabled. Chips or a list of names would have been
 * shorter, and would have made reading and changing two different pictures of the same
 * thing: somebody who learns where a cell is here would then have to find it again over
 * there. The disabled grid costs nothing to keep true, because there is only one of it.
 *
 * Above it goes the reach bar, which is the one thing the grid cannot say about itself
 * without being read row by row.
 */
final class ViewRole extends ViewRecord
{
    use FillsRoleAbilities;

    protected static string $resource = RoleResource::class;

    /**
     * How far the role reaches, before a single row of it is read.
     */
    public function getSubheading(): Htmlable
    {
        // The analyser works out `view-string` by looking for the file among the paths the
        // application renders from, and a package's namespaced view is never among them:
        // it would have to be called `roles.coverage` in the consuming application to be
        // found at all. The annotation says what it has no way of working out; the record
        // page test is what proves the file is really there.
        /** @var view-string $view */
        $view = 'filament-bouncer::roles.coverage';

        return new HtmlString(view($view, [
            'coverage' => RoleCoverage::for($this->getRecord(), app(CatalogRegistry::class)->current()),
        ])->render());
    }

    /**
     * The way in, offered only where there is one.
     *
     * The resource refuses to edit your own role and the way back in, and this asks it
     * rather than the policy: a record page that offered a door onto a page which aborts
     * would be worse than one that offers none.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn (): string => RoleResource::getUrl('edit', ['record' => $this->getRecord()]))
                ->visible(fn (): bool => RoleResource::canEdit($this->getRecord())),
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
}
