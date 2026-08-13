<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * A rule read, with the same sections as the form and in the same order, so that whoever learns
 * where something is on one screen finds it in the same place on the other.
 *
 * What moves is the tenant: it is not written here, so it goes to the narrow column with the
 * metadata — and it is not drawn at all where the installation does not scope its rows.
 *
 * The blanks say what each one means rather than showing a dash: a record left empty is "all of
 * them", a model left empty is "none".
 */
final class AbilityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('filament-bouncer::abilities.form.rule'))
                            ->description(__('filament-bouncer::abilities.form.rule_note'))
                            ->icon('heroicon-o-key')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('filament-bouncer::abilities.form.name'))
                                    ->badge()
                                    ->copyable(),

                                TextEntry::make('title')
                                    ->label(__('filament-bouncer::titles.label'))
                                    ->placeholder(__('filament-bouncer::abilities.record.name_empty')),
                            ]),

                        Section::make(__('filament-bouncer::abilities.form.reach'))
                            ->description(__('filament-bouncer::abilities.form.reach_note'))
                            ->icon('heroicon-o-viewfinder-circle')
                            ->schema([
                                TextEntry::make('entity_type')
                                    ->label(__('filament-bouncer::abilities.form.model'))
                                    ->placeholder(__('filament-bouncer::abilities.record.model_none')),

                                TextEntry::make('entity_id')
                                    ->label(__('filament-bouncer::abilities.form.record'))
                                    ->numeric()
                                    ->placeholder(__('filament-bouncer::abilities.record.record_all')),

                                IconEntry::make('only_owned')
                                    ->label(__('filament-bouncer::abilities.form.owned'))
                                    ->boolean()
                                    ->falseIcon('heroicon-m-minus')
                                    ->falseColor('gray'),
                            ]),

                        Section::make(__('filament-bouncer::abilities.form.restrictions'))
                            ->description(__('filament-bouncer::abilities.form.restrictions_note'))
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                KeyValueEntry::make('options')
                                    ->hiddenLabel()
                                    ->keyLabel(__('filament-bouncer::abilities.form.options_key'))
                                    ->valueLabel(__('filament-bouncer::abilities.form.options_value'))
                                    ->placeholder(__('filament-bouncer::abilities.form.options_empty')),
                            ]),

                        HealthSection::make(),
                    ])
                    ->columnSpan(['lg' => 3]),

                Group::make()
                    ->schema([
                        Section::make(__('filament-bouncer::abilities.form.tenant'))
                            ->visible(fn (): bool => resolve(Tenancy::class)->inUse())
                            ->schema([
                                TextEntry::make('scope')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->placeholder(__('filament-bouncer::abilities.form.scope_global')),
                            ]),

                        Section::make(__('filament-bouncer::abilities.form.metadata'))
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('filament-bouncer::abilities.form.created'))
                                    ->isoDate('lll'),

                                TextEntry::make('updated_at')
                                    ->label(__('filament-bouncer::abilities.form.updated'))
                                    ->isoDate('lll'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
