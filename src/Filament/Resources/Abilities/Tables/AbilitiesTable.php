<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\Ailment;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every rule the store holds, with what only this screen can say about them.
 *
 * The health column is the reason the workbench exists: it reports four things that are true in the
 * store, that nothing else detects, and that can only be mended from here. It is **one icon** with
 * three states and the whole account in its tooltip — a row broken three ways stacked three badges
 * in a cell and widened the table to say something you have to open the record page to act on
 * anyway. The listing answers "is there anything, and how bad"; the record page answers "what
 * exactly".
 *
 * The declaration column stays from the screen this one replaces, and it earns its place for the
 * same reason it did there: **it is what stands in for a delete button**. Knowing whether a row is
 * declared, adrift, or was never the reconciliation's business says far more about how it will end
 * than a button that would take every grant pointing at it along in one click.
 *
 * The model and the record are one column, because separately they have to be combined in the
 * reader's head — "User" and "all" say nothing on their own. And the reach only speaks when it is
 * not the ordinary one: saying "all records" under nine rows in ten drowns the two that are fenced.
 */
final class AbilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-bouncer::abilities.table.name'))
                    ->badge()
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('filament-bouncer::abilities.table.title'))
                    ->searchable()
                    ->placeholder(__('filament-bouncer::abilities.table.title_empty')),

                TextColumn::make('entity_type')
                    ->label(__('filament-bouncer::abilities.table.reach'))
                    ->searchable()
                    ->formatStateUsing(self::basename(...))
                    ->description(self::reach(...))
                    ->tooltip(fn (Model $record): ?string => self::text($record->getAttribute('entity_type')))
                    ->placeholder(__('filament-bouncer::abilities.table.model_none')),

                IconColumn::make('only_owned')
                    ->label(__('filament-bouncer::abilities.table.owned'))
                    ->boolean()
                    // The default "no" is a red cross, and almost every rule says no: the whole
                    // column read as a wall of errors and drowned the one that does warn.
                    ->falseIcon('heroicon-m-minus')
                    ->falseColor('gray'),

                TextColumn::make('declared')
                    ->label(__('filament-bouncer::abilities.declared.label'))
                    ->badge()
                    ->state(static fn (Model $record): string => Declaration::of($record)->label())
                    ->color(static fn (Model $record): string => Declaration::of($record)->color()),

                IconColumn::make('health')
                    ->label(__('filament-bouncer::abilities.health.column'))
                    ->alignCenter()
                    ->state(fn (Model $record): string => resolve(Diagnosis::class)->severity($record))
                    // These receive `$state`, not a parameter named to taste: Filament resolves
                    // closures by parameter name, so one badly christened takes the table down.
                    ->icon(fn (string $state): string => match ($state) {
                        Diagnosis::SEVERE => 'heroicon-m-exclamation-circle',
                        Diagnosis::HIDDEN => 'heroicon-m-eye-slash',
                        default => 'heroicon-m-check-circle',
                    })
                    ->color(fn (string $state): string => $state === Diagnosis::HEALTHY ? 'gray' : $state)
                    ->tooltip(fn (Model $record): string => self::ailmentNotes($record)
                        ?? __('filament-bouncer::abilities.health.clean')),

                TextColumn::make('scope')
                    ->label(__('filament-bouncer::abilities.form.scope'))
                    ->numeric()
                    ->sortable()
                    ->placeholder(__('filament-bouncer::abilities.form.scope_global'))
                    ->visible(fn (): bool => resolve(Tenancy::class)->inUse()),

                TextColumn::make('created_at')
                    ->label(__('filament-bouncer::abilities.form.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('filament-bouncer::abilities.form.updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('health')
                    ->label(__('filament-bouncer::abilities.health.column'))
                    ->options(Ailment::class)
                    ->query(self::onlyAiling(...)),
            ])
            ->defaultSort('name')
            ->recordActions([
                // The long label ate a third of the width repeated on every row. What it means fits
                // in the tooltip, which is only paid for by pointing at it.
                Action::make('narrow')
                    ->label(__('filament-bouncer::abilities.table.narrow'))
                    ->tooltip(__('filament-bouncer::abilities.table.narrow_note'))
                    ->icon('heroicon-m-viewfinder-circle')
                    ->url(self::narrowUrl(...)),
                ViewAction::make(),
                EditAction::make()
                    ->visible(static fn (Model $record): bool => AbilityResource::canEdit($record)),
                // The same padlock the roles screen shows over the role that is the way back in,
                // and for the same reason: this row is what makes that role hold everything.
                Action::make('locked')
                    ->hiddenLabel()
                    ->iconButton()
                    ->icon('heroicon-o-lock-closed')
                    ->disabled()
                    ->tooltip(__('filament-bouncer::abilities.table.locked'))
                    ->visible(static fn (Model $record): bool => AbilityResource::isLocked($record)),
            ])
            ->emptyStateHeading(__('filament-bouncer::abilities.table.empty'))
            ->emptyStateDescription(__('filament-bouncer::abilities.table.empty_note'));
    }

    /**
     * Composing a rule starts from one that already exists, with the record left blank — the one
     * thing whoever is fencing it came to write.
     */
    private static function narrowUrl(Model $record): string
    {
        return AbilityResource::getUrl('create', array_filter([
            'name' => $record->getAttribute('name'),
            'entity_type' => $record->getAttribute('entity_type'),
            'only_owned' => $record->getAttribute('only_owned') ? '1' : null,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $data
     * @return Builder<Model>
     */
    private static function onlyAiling(Builder $query, array $data): Builder
    {
        $value = $data['value'] ?? null;
        $ailment = is_string($value) ? Ailment::tryFrom($value) : null;

        return $ailment instanceof Ailment
            ? $query->whereKey(resolve(Diagnosis::class)->keysWith($ailment))
            : $query;
    }

    /**
     * How far the rule reaches inside the model it names, and only when that is not the ordinary
     * thing. Silence means "all of them", the same convention as the dash under "only owned".
     */
    private static function reach(Model $record): ?string
    {
        $type = $record->getAttribute('entity_type');
        $id = $record->getAttribute('entity_id');

        return match (true) {
            $id !== null => (string) __('filament-bouncer::abilities.table.reach_record', ['id' => self::text($id) ?? '']),
            $type === AbilityStore::WILDCARD => (string) __('filament-bouncer::abilities.table.reach_any'),
            default => null,
        };
    }

    private static function basename(?string $state): ?string
    {
        return $state === null ? null : basename(str_replace('\\', '/', $state));
    }

    /**
     * Everything wrong with the row, whole, for the icon's tooltip. The icon sums up; this tells.
     */
    private static function ailmentNotes(Model $record): ?string
    {
        $notes = array_map(
            static fn (Ailment $ailment): string => $ailment->note(),
            resolve(Diagnosis::class)->of($record),
        );

        return $notes === [] ? null : implode(' ', $notes);
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
