<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The roles, as a listing.
 *
 * What a role can do is not answered here. It used to be, with a bar drawing the catalogue
 * to scale on every row — and a figure summing up a whole catalogue tells nobody whether
 * this role may delete accounts, which is what anybody comes to a listing to find out. The
 * record page says it subject by subject, and that is one click away.
 *
 * The destructive way lives behind the kebab, and the two rows nobody works on from here
 * carry a padlock in its place: an explanation where a silently missing button would read
 * as a bug.
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
            ->defaultSort('name')
            ->emptyStateHeading(__('filament-bouncer::roles.table.empty'));
    }
}
