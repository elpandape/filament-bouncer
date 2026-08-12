<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Database\Models;

final class ListAbilities extends ListRecords
{
    protected static string $resource = AbilityResource::class;

    /**
     * The parent's content with the summary chips slotted in above the table, which is
     * where the approved design keeps them: outside the card, so they read as the
     * screen's figures and not as one more toolbar.
     */
    public function content(Schema $schema): Schema
    {
        // The analyser works out `view-string` by looking for the file among the paths
        // the application renders from, and a package's namespaced view is never among
        // them.
        /** @var view-string $chips */
        $chips = 'filament-bouncer::tables.ability-chips';

        return $schema->components([
            $this->getTabsContentComponent(),
            View::make($chips)->viewData(fn (): array => $this->figures()),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    /**
     * The one thing that may be composed here is a narrowed rule, and the button says so
     * rather than saying "new": everything else on this screen was written by the
     * reconciliation and cannot be written from a browser at all.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('filament-bouncer::abilities.narrow')),
        ];
    }

    /**
     * The chips' four figures. The denials are counted off the pivot's own `forbidden`
     * column and per rule rather than per grant, because the chip answers "how many
     * rules are forbidden to somebody" and one rule forbidden to three roles is still
     * one rule.
     *
     * @return array{all: int, narrowed: int, wildcard: int, forbidden: int}
     */
    private function figures(): array
    {
        return [
            'all' => Models::ability()->newQuery()->count(),
            'narrowed' => Models::ability()->newQuery()
                ->where(static function (Builder $query): void {
                    $query->whereNotNull('entity_id')->orWhere('only_owned', true);
                })
                ->count(),
            'wildcard' => Models::ability()->newQuery()
                ->where(static function (Builder $query): void {
                    $query->where('name', AbilityStore::WILDCARD)->orWhere('entity_type', AbilityStore::WILDCARD);
                })
                ->count(),
            'forbidden' => DB::table(Models::table('permissions'))
                ->where('forbidden', true)
                ->distinct()
                ->count('ability_id'),
        ];
    }
}
