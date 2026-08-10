<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityHolders;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The ability read from the other end: what it is, and who holds it.
 *
 * One field is the reader's, and the form says so by disabling the rest. The name is
 * what the code asks the Gate and the entity is the model it asks about: both are
 * declarations, and rewriting either here would leave the store answering a question
 * nothing puts to it. The title is read by people and by nothing else.
 */
final class AbilityForm
{
    public const string HOLDERS = 'holders';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-bouncer::abilities.ability'))
                ->description(__('filament-bouncer::abilities.declared'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('filament-bouncer::abilities.title_field'))
                        ->helperText(__('filament-bouncer::abilities.retitle_note'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('name')
                        ->label(__('filament-bouncer::abilities.name_field'))
                        ->disabled(),
                    TextInput::make('entity_type')
                        ->label(__('filament-bouncer::abilities.entity_field'))
                        ->placeholder(__('filament-bouncer::abilities.no_entity'))
                        ->disabled(),
                ])
                ->columns(2),
            Section::make(__('filament-bouncer::abilities.holders_section'))
                ->description(__('filament-bouncer::abilities.holders_note'))
                ->columnSpanFull()
                ->schema([
                    AbilityHolders::make(self::HOLDERS)
                        ->hiddenLabel(),
                ]),
        ]);
    }
}
