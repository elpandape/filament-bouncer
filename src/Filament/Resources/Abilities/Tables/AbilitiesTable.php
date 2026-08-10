<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
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
                // How far the rule reaches, which is the one thing about a row that the
                // name cannot say: `update` on posts and `update` on the posts you wrote
                // are the same two words and different rules.
                TextColumn::make('reach')
                    ->label(__('filament-bouncer::abilities.reach'))
                    ->badge()
                    ->color('warning')
                    ->state(static fn (Model $record): array => self::reach($record))
                    ->placeholder(__('filament-bouncer::abilities.reach_all')),
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
                // reconciliation would take away. Three answers, not two — a row nothing
                // declares is drift and `--prune` removes it without asking twice, but a
                // row the reconciliation never spoke for is in no danger at all, and
                // reading the same warning against both would teach an operator to
                // ignore it.
                TextColumn::make('declared')
                    ->label(__('filament-bouncer::abilities.declared_column'))
                    ->badge()
                    ->state(static fn (Model $record): string => __(self::standing($record)[0]))
                    ->color(static fn (Model $record): string => self::standing($record)[1]),
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
        if (app(AbilityStore::class)->isRestricted($record)) {
            return self::narrowedHolders($record);
        }

        /** @var string $name */
        $name = $record->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $record->getAttribute('entity_type');

        $identity = Ability::identityFor($name, $entityType);
        $abilities = app(RoleAbilities::class);
        $held = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $subject) {
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

    /**
     * The roles holding one narrowed row, read off the row itself.
     *
     * @return array<int, string>
     */
    private static function narrowedHolders(Model $record): array
    {
        $abilities = app(RoleAbilities::class);
        $held = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var Model $role */
            if ($abilities->stanceOnRow($role, $record) !== Stance::Granted) {
                continue;
            }

            /** @var string $name */
            $name = $role->getAttribute('name');

            $held[] = $name;
        }

        return $held;
    }

    /**
     * Where the row stands with the reconciliation, as a line and a colour.
     *
     * @return array{0: string, 1: string}
     */
    private static function standing(Model $record): array
    {
        if (! app(AbilityStore::class)->speaksFor($record)) {
            return ['filament-bouncer::abilities.declared_apart', 'gray'];
        }

        /** @var string $name */
        $name = $record->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $record->getAttribute('entity_type');

        $identity = Ability::identityFor($name, $entityType);

        foreach (app(CatalogRegistry::class)->current()->abilities() as $ability) {
            if ($ability->identity() === $identity) {
                return ['filament-bouncer::abilities.declared_yes', 'success'];
            }
        }

        return ['filament-bouncer::abilities.declared_no', 'warning'];
    }

    /**
     * How far a row reaches, in as many words as it takes.
     *
     * @return array<int, string>
     */
    private static function reach(Model $record): array
    {
        $reach = [];

        if ($record->getAttribute('only_owned')) {
            $reach[] = __('filament-bouncer::abilities.owned_suffix');
        }

        /** @var scalar|null $id */
        $id = $record->getAttribute('entity_id');

        if ($id !== null) {
            $reach[] = __('filament-bouncer::abilities.record_suffix', ['id' => (string) $id]);
        }

        return $reach;
    }
}
