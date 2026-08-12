<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Database\Models;

/**
 * The roles, and how far each of them reaches.
 *
 * A name on its own says nothing about what a role can do, and the answer is the whole
 * point of the screen: hence the bar, which is the catalogue drawn to scale — what the
 * role grants, what it denies, and what it leaves alone.
 *
 * Reading and changing are drawn as text links, the destructive way lives behind the
 * kebab, and the two rows nobody works on from here carry a padlock in its place: an
 * explanation where a silently missing button would read as a bug.
 *
 * There are no bulk actions here, and there is no adding any. Filament authorises a bulk
 * delete once for the whole selection, so a single yes would walk past both refusals the
 * resource makes per record; the roles policy declares no `deleteAny` for the same
 * reason.
 */
final class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-bouncer::roles.table.name'))
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->description(self::title(...)),
                CoverageColumn::make('coverage')
                    ->label(__('filament-bouncer::roles.table.coverage'))
                    ->state(self::reach(...)),
                TextColumn::make('holders')
                    ->label(__('filament-bouncer::roles.table.holders'))
                    ->state(self::holders(...)),
                TextColumn::make('updated_at')
                    ->label(__('filament-bouncer::roles.table.updated'))
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->link()
                    ->icon(null),
                EditAction::make()
                    ->link()
                    ->icon(null)
                    ->visible(static fn (Model $record): bool => RoleResource::canEdit($record)),
                ActionGroup::make([
                    DeleteAction::make()
                        ->visible(static fn (Model $record): bool => RoleResource::canDelete($record)),
                ])->visible(static fn (Model $record): bool => ! RoleResource::isLocked($record)),
                Action::make('locked')
                    ->hiddenLabel()
                    ->iconButton()
                    ->icon('heroicon-o-lock-closed')
                    ->disabled()
                    ->tooltip(__('filament-bouncer::roles.table.locked'))
                    ->visible(static fn (Model $record): bool => RoleResource::isLocked($record)),
            ])
            ->searchPlaceholder(__('filament-bouncer::roles.table.search'))
            ->contentFooter(static function (): View {
                // The analyser works out `view-string` by looking for the file among the
                // paths the application renders from, and a package's namespaced view is
                // never among them.
                /** @var view-string $legend */
                $legend = 'filament-bouncer::tables.catalog-legend';

                return view($legend, ['total' => self::catalogTotal()]);
            })
            ->defaultSort('name')
            ->emptyStateHeading(__('filament-bouncer::roles.table.empty'));
    }

    private static function title(Model $record): ?string
    {
        /** @var string|null $title */
        $title = $record->getAttribute('title');

        return $title;
    }

    /**
     * How many accounts hold the role.
     *
     * Counted through the pivot rather than a relation, because the role model is
     * whatever the application configured and nothing promises the analyser that it
     * carries Bouncer's traits.
     */
    private static function holders(Model $record): int
    {
        return DB::table(Models::table('assigned_roles'))
            ->where('role_id', $record->getKey())
            ->count();
    }

    /**
     * How many cells every bar is drawn against, for the legend under the table.
     */
    private static function catalogTotal(): int
    {
        $total = 0;

        foreach (app(CatalogRegistry::class)->current()->subjects as $subject) {
            $total += count($subject->cells());
        }

        return $total;
    }

    /**
     * The bar, worked out here so the view has nothing to decide.
     *
     * A role reaching everything through the wildcard holds no rule of its own for any
     * cell, so drawing the bar from its grants alone would report that it can do nothing
     * at all. It is drawn full instead, and says why in words.
     *
     * @return array{granted: int, forbidden: int, neutral: int, total: int, reaches_all: bool, shares: array{granted: float, forbidden: float, neutral: float}}
     */
    private static function reach(Model $record): array
    {
        $coverage = RoleCoverage::for($record, app(CatalogRegistry::class)->current());

        if ($coverage->reachesAll) {
            return [
                'granted' => $coverage->granted,
                'forbidden' => $coverage->forbidden,
                'neutral' => $coverage->neutral,
                'total' => $coverage->total,
                'reaches_all' => true,
                'shares' => ['granted' => 100.0, 'forbidden' => 0.0, 'neutral' => 0.0],
            ];
        }

        return [
            'granted' => $coverage->granted,
            'forbidden' => $coverage->forbidden,
            'neutral' => $coverage->neutral,
            'total' => $coverage->total,
            'reaches_all' => false,
            'shares' => [
                'granted' => self::share($coverage->granted, $coverage->total),
                'forbidden' => self::share($coverage->forbidden, $coverage->total),
                'neutral' => self::share($coverage->neutral, $coverage->total),
            ],
        ];
    }

    /**
     * The floor of one keeps a catalogue that declares nothing from dividing by zero. The
     * numerator is zero in that case too, so the segment comes out empty either way.
     */
    private static function share(int $part, int $total): float
    {
        return round($part / max($total, 1) * 100, 2);
    }
}
