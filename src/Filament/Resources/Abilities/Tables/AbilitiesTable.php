<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

final class AbilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('filament-bouncer::abilities.title_field'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('filament-bouncer::abilities.name_field'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entity_type')
                    ->label(__('filament-bouncer::abilities.entity_field'))
                    ->placeholder(__('filament-bouncer::abilities.no_entity'))
                    ->searchable()
                    ->toggleable(),
                // What P4 put where a count would have gone: not how many roles hold it
                // but how each of them came to. A wildcard nobody remembers handing out
                // is invisible in a number and obvious in these.
                TextColumn::make('holders')
                    ->label(__('filament-bouncer::abilities.holders'))
                    ->badge()
                    ->color(static fn (string $state): string => str_contains($state, '·') ? 'warning' : 'success')
                    ->state(static fn (Model $record): array => self::holders($record))
                    ->placeholder(__('filament-bouncer::abilities.nobody_short')),
                // The one thing an operator cannot see anywhere else: which rows the
                // reconciliation would take away. A row nothing declares is not a
                // mistake in itself — an application may check a name of its own — but
                // it is drift, and `--prune` removes it without asking twice.
                TextColumn::make('declared')
                    ->label(__('filament-bouncer::abilities.declared_column'))
                    ->badge()
                    ->state(static fn (Model $record): string => self::isDeclared($record)
                        ? __('filament-bouncer::abilities.declared_yes')
                        : __('filament-bouncer::abilities.declared_no'))
                    ->color(static fn (Model $record): string => self::isDeclared($record) ? 'success' : 'warning'),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('only_owned')
                    ->label(__('filament-bouncer::abilities.only_owned'))
                    ->queries(
                        true: static fn (Builder $query): Builder => $query->where('only_owned', true),
                        false: static fn (Builder $query): Builder => $query->where('only_owned', false),
                        blank: static fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * Each role that answers yes, and whether anybody actually said so.
     *
     * @return array<int, string>
     */
    private static function holders(Model $record): array
    {
        /** @var string $name */
        $name = $record->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $record->getAttribute('entity_type');

        $identity = Ability::identityFor($name, $entityType);
        $abilities = app(RoleAbilities::class);
        $held = [];

        foreach (app(EditableCatalog::class)->current()->subjects as $subject) {
            foreach ($subject->cells() as $action => $ability) {
                if ($ability->identity() !== $identity) {
                    continue;
                }

                foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
                    /** @var Model $role */
                    /** @var string $roleName */
                    $roleName = $role->getAttribute('name');

                    $direct = Stance::tryFrom($abilities->toFormState($role)[$subject->key][$action] ?? '') ?? Stance::Neutral;
                    $holds = $abilities->holds($role, $ability);

                    if ($direct === Stance::Granted) {
                        $held[] = $roleName;
                    } elseif ($direct === Stance::Neutral && $holds) {
                        $held[] = $roleName.' · '.__('filament-bouncer::abilities.broader_short');
                    }
                }
            }
        }

        return $held;
    }

    private static function isDeclared(Model $record): bool
    {
        /** @var string $name */
        $name = $record->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $record->getAttribute('entity_type');

        return app(AbilityStore::class)->find($name, $entityType) !== null;
    }
}
