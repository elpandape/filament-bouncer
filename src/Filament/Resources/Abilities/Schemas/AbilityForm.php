<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityHolders;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What a rule is, and who says what about it.
 *
 * One field on this screen is written and three are not, and the split is the point. The
 * name the code hands the Gate and the model it hands along with it are declarations:
 * they are a policy method, a page, a widget or a key in configuration, and
 * `filament-bouncer:reconcile` is what writes them. Rewriting either here would leave a
 * row nothing ever asks about, and Bouncer never complains about a name that does not
 * exist — it just answers no, for ever, to everybody.
 *
 * The title is the exception because the title is read by people and by nothing else.
 */
final class AbilityForm
{
    /**
     * Where every role's stance on this rule lives in the form state.
     */
    public const string HOLDERS = 'holders';

    /**
     * How far the rule goes, read out of the row rather than stored on it.
     */
    public const string REACH = 'reach';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-bouncer::abilities.form.rule'))
                ->description(__('filament-bouncer::abilities.form.declared_note'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('filament-bouncer::abilities.form.name'))
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('entity_type')
                        ->label(__('filament-bouncer::abilities.form.entity'))
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(static fn (mixed $state): string => is_string($state)
                            ? $state
                            : __('filament-bouncer::abilities.form.no_entity')),
                    TextInput::make(self::REACH)
                        ->label(__('filament-bouncer::abilities.form.reach'))
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('title')
                        ->label(__('filament-bouncer::abilities.form.title'))
                        ->maxLength(150)
                        ->helperText(__('filament-bouncer::abilities.form.title_note')),
                ])
                ->columns(2),
            Section::make(__('filament-bouncer::abilities.form.holders'))
                ->description(__('filament-bouncer::abilities.form.holders_note'))
                ->schema([self::holders()])
                ->columnSpanFull(),
        ]);
    }

    public static function holders(): AbilityHolders
    {
        return AbilityHolders::make(self::HOLDERS)
            ->hiddenLabel()
            ->default([]);
    }
}
