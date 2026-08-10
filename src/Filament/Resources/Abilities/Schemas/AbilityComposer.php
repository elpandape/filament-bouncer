<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Silber\Bouncer\Database\Models;

/**
 * The one ability a screen may make: a narrowed one.
 *
 * The catalogue owns the plain row — the one that says "may update posts" — and the
 * reconciliation writes it from the code that asks about it. What the code cannot say is
 * "may update the posts they wrote" or "may update post 7": those live in columns Bouncer
 * has and a policy method does not, and the reconciliation deliberately never speaks for
 * them, so nothing here makes work for the next deploy.
 *
 * The form is therefore not a blank row with five fields. It is a sentence being built:
 * pick the model, pick the action, then say how far it reaches. The name and the model
 * are taken from the catalogue entry those two choices land on, which is what keeps a
 * narrowed rule spelled the same way as the rule it narrows — Bouncer answers `false` to
 * a name nobody declared, and never says the name was wrong.
 */
final class AbilityComposer
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-bouncer::abilities.narrow'))
                ->description(__('filament-bouncer::abilities.narrow_note'))
                ->schema([
                    Select::make('subject')
                        ->label(__('filament-bouncer::abilities.entity_field'))
                        ->options(self::subjects(...))
                        ->required()
                        ->live()
                        ->afterStateUpdated(static function (Set $set): void {
                            // The actions on offer are the new subject's, so a choice
                            // made against the old one would be a name that no longer
                            // means anything here.
                            $set('action', null);
                            $set('title', null);
                        }),
                    Select::make('action')
                        ->label(__('filament-bouncer::abilities.action_field'))
                        ->options(static fn (Get $get): array => self::actions($get('subject')))
                        ->required()
                        ->live()
                        ->rule(static fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            self::refuseDuplicate($get, $fail);
                        })
                        ->afterStateUpdated(static fn (Get $get, Set $set): mixed => $set('title', self::compose($get))),
                    Toggle::make('only_owned')
                        ->label(__('filament-bouncer::abilities.only_owned'))
                        ->helperText(__('filament-bouncer::abilities.only_owned_note'))
                        ->live()
                        ->rule(static fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            // Neither box ticked composes the plain row, which is the one
                            // the reconciliation writes and would then find twice.
                            if (! $value && blank($get('entity_id'))) {
                                $fail(__('filament-bouncer::abilities.narrow_required'));
                            }
                        })
                        ->afterStateUpdated(static fn (Get $get, Set $set): mixed => $set('title', self::compose($get))),
                    TextInput::make('entity_id')
                        ->label(__('filament-bouncer::abilities.record_field'))
                        ->helperText(__('filament-bouncer::abilities.record_note'))
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(static fn (Get $get, Set $set): mixed => $set('title', self::compose($get))),
                    TextInput::make('title')
                        ->label(__('filament-bouncer::abilities.title_field'))
                        ->helperText(__('filament-bouncer::abilities.compose_note'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    /**
     * The catalogue entry two choices land on, if they land on one.
     */
    public static function ability(mixed $subject, mixed $action): ?Ability
    {
        $found = self::subject($subject);

        return $found instanceof Subject && is_string($action)
            ? $found->cells()[$action] ?? null
            : null;
    }

    /**
     * Only the subjects that stand for a model.
     *
     * A page or a widget is reached or it is not: there is no record to point at and
     * nothing to own, so narrowing one would compose a row nothing can ever match.
     *
     * @return array<string, string>
     */
    private static function subjects(): array
    {
        $subjects = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $key => $subject) {
            if ($subject->entityType !== null) {
                $subjects[$key] = $subject->label;
            }
        }

        return $subjects;
    }

    /**
     * @return array<string, string>
     */
    private static function actions(mixed $key): array
    {
        $subject = self::subject($key);

        if (! $subject instanceof Subject) {
            return [];
        }

        $labels = app(Labels::class);
        $actions = [];

        foreach (array_keys($subject->cells()) as $action) {
            $actions[$action] = $labels->action($action);
        }

        return $actions;
    }

    private static function subject(mixed $key): ?Subject
    {
        return is_string($key)
            ? app(CatalogRegistry::class)->current()->subject($key)
            : null;
    }

    /**
     * The title, written out as the choices are made.
     *
     * It is a suggestion and not a fact: the field stays editable, because the title is
     * read by people and by nothing else. What it saves is having to write out by hand
     * the one thing the row will not show on its own — how far it reaches.
     */
    private static function compose(Get $get): string
    {
        $ability = self::ability($get('subject'), $get('action'));

        if (! $ability instanceof Ability) {
            return '';
        }

        $narrowings = [];

        if ($get('only_owned')) {
            $narrowings[] = __('filament-bouncer::abilities.owned_suffix');
        }

        /** @var scalar|null $record */
        $record = $get('entity_id');

        if (filled($record)) {
            $narrowings[] = __('filament-bouncer::abilities.record_suffix', ['id' => (string) $record]);
        }

        return $narrowings === []
            ? $ability->title
            : $ability->title.' — '.implode(', ', $narrowings);
    }

    private static function refuseDuplicate(Get $get, Closure $fail): void
    {
        $ability = self::ability($get('subject'), $get('action'));

        if (! $ability instanceof Ability) {
            return;
        }

        $id = filled($get('entity_id')) ? $get('entity_id') : null;

        $exists = Models::ability()->newQuery()
            ->where('name', $ability->name)
            ->where('entity_type', $ability->entityMorphClass)
            ->where('only_owned', (bool) $get('only_owned'))
            ->where(static fn (mixed $query): mixed => $id === null
                ? $query->whereNull('entity_id')
                : $query->where('entity_id', $id))
            ->exists();

        if ($exists) {
            $fail(__('filament-bouncer::abilities.duplicate'));
        }
    }
}
