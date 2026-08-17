<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables;

use ElPandaPe\FilamentBouncer\Events\RoleDeletedEvent;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Support\Causer;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * The roles, as a listing.
 *
 * What a role can do is not answered here: a figure summing a whole catalogue tells nobody whether
 * this role may delete accounts, and the record page says it entity by entity one click away.
 *
 * The two rows nobody works on from here carry a padlock where the actions would be, since a
 * silently missing button reads as a bug.
 *
 * There are no bulk actions and none are to be added: Filament authorises a bulk delete once for
 * the whole selection, so a single yes would walk past both refusals the resource makes per record.
 */
final class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-bouncer::roles.table.name'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('filament-bouncer::roles.table.title'))
                    ->searchable(),
                TextColumn::make('scope')
                    ->label(__('filament-bouncer::roles.table.scope'))
                    ->numeric()
                    ->sortable()
                    ->placeholder(__('filament-bouncer::roles.record.scope_global'))
                    // A column every row answers the same way is a column that only costs width.
                    ->visible(fn (): bool => resolve(Tenancy::class)->inUse()),
                TextColumn::make('created_at')
                    ->label(__('filament-bouncer::roles.table.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament-bouncer::roles.table.updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(static fn (Model $record): bool => RoleResource::canEdit($record)),
                ActionGroup::make([
                    DeleteAction::make()
                        ->visible(static fn (Model $record): bool => RoleResource::canDelete($record))
                        ->using(static function (Model $record): void {
                            // Read before the delete: the foreign key retracts every holder and
                            // Bouncer's own hook detaches every ability, so afterwards there is
                            // nothing left to tell.
                            $departure = self::departure($record);

                            $record->delete();

                            // Bouncer clears nothing of its own accord, and the clipboard would
                            // go on answering yes for the rest of this request.
                            Bouncer::refresh();

                            event($departure);
                        }),
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
            ->defaultSort('name')
            ->emptyStateHeading(__('filament-bouncer::roles.table.empty'));
    }

    /**
     * What is about to be lost with the role: its holders, resolved through the pivot, and how
     * many stances it carried. A morph type that no longer resolves to a class is left out —
     * there is no model to hand a listener.
     */
    private static function departure(Model $role): RoleDeletedEvent
    {
        $holders = new Collection;

        $rows = DB::table(Models::table('assigned_roles'))
            ->where('role_id', $role->getKey())
            ->get(['entity_type', 'entity_id']);

        foreach ($rows as $row) {
            /** @var string $entityType */
            $entityType = $row->entity_type;

            $class = Relation::getMorphedModel($entityType) ?? $entityType;

            if (! class_exists($class)) {
                continue;
            }

            if (! is_a($class, Model::class, true)) {
                continue;
            }

            $holder = $class::query()->find($row->entity_id);

            if ($holder instanceof Model) {
                $holders->push($holder);
            }
        }

        /** @var string $name */
        $name = $role->getAttribute('name');

        return new RoleDeletedEvent($name, $holders, self::stanceCount($role), Causer::current());
    }

    private static function stanceCount(Model $role): int
    {
        return DB::table(Models::table('permissions'))
            ->where('entity_id', $role->getKey())
            ->where('entity_type', $role->getMorphClass())
            ->count();
    }
}
