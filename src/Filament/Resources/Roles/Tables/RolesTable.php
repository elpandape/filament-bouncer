<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('abilities_count')
                    ->label('Abilities')
                    ->counts('abilities'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                // These two send the reader to the pages, because Filament resolves the
                // resource's own routes for them. Left to open a modal they would render
                // the form, and the grid it carries is not an attribute of the role:
                // only the pages know to take it back out before the record is written.
                // That is a promise of Filament's rather than of this package's, so a
                // test pins it.
                ViewAction::make(),
                EditAction::make()
                    // The two safeguards live on the resource, not in a policy, so the
                    // row has to ask it. Nothing else hides these buttons.
                    ->visible(static fn (Model $record): bool => RoleResource::canEdit($record)),
                DeleteAction::make()
                    ->visible(static fn (Model $record): bool => RoleResource::canDelete($record)),
            ]);
    }
}
