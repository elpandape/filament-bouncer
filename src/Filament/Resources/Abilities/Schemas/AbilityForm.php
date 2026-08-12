<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityHolders;
use ElPandaPe\FilamentBouncer\Support\AbilityFacts;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * What a rule is, and who says what about it.
 *
 * One field on this screen is written and three are not, and the split is the point. The
 * name the code hands the Gate and the model it hands along with it are declarations:
 * they are a policy method, a page, a widget or a key in configuration, and
 * `filament-bouncer:reconcile` is what writes them. That is why the three are presented
 * as entries rather than disabled inputs — a greyed field reads as something broken,
 * an entry reads as something settled, and settled is what they are. Rewriting either
 * would leave a row nothing ever asks about, and Bouncer never complains about a name
 * that does not exist — it just answers no, for ever, to everybody.
 *
 * The title is the exception because the title is read by people and by nothing else.
 */
final class AbilityForm
{
    /**
     * Where every role's stance on this rule lives in the form state.
     */
    public const string HOLDERS = 'holders';

    public static function configure(Schema $schema): Schema
    {
        // The analyser works out `view-string` by looking for the file among the paths
        // the application renders from, and a package's namespaced view is never among
        // them.
        /** @var view-string $facts */
        $facts = 'filament-bouncer::forms.ability-facts';

        return $schema->components([
            Section::make(__('filament-bouncer::abilities.form.rule'))
                ->description(__('filament-bouncer::abilities.form.declared_note'))
                ->schema([
                    View::make($facts)
                        ->viewData(static fn (?Model $record): array => [
                            'facts' => $record instanceof Model ? AbilityFacts::of($record) : null,
                        ])
                        ->columnSpanFull(),
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
