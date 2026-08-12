<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Forms\ActionCards;
use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Silber\Bouncer\Database\Models;

/**
 * Composing the one kind of row the reconciliation never writes.
 *
 * The plain rule — "may change posts" — is a declaration: the code that asks the Gate
 * about it is what declares it, and `filament-bouncer:reconcile` is what writes it down.
 * What the code has no way of saying is how far the rule should reach, and that is the
 * whole of this screen: a rule held down to one record, or to whatever its holder owns.
 * Those two are exactly the rows `--check` does not fail on and `--prune` does not sweep,
 * which is why composing them here is safe and composing a plain one would not be.
 *
 * The name and the model are taken from the catalogue and never from the request. The
 * model's pull-down and the action's cards choose which cell of the catalogue is meant;
 * what gets written is whatever that cell says it is. So a rule about a whole model is
 * stored as Bouncer's own wildcard and not as the word `manage` the column was labelled
 * with — and a request that names something else finds nothing here that reads it.
 */
final class NarrowAbility
{
    public const string SUBJECT = 'subject';

    public const string ACTION = 'action';

    public const string REACH = 'reach';

    public const string RECORD = 'record';

    public const string TITLE = 'title';

    /**
     * The action is cards rather than a second pull-down, because the choice is the
     * whole step and the approved design lays it out to be read at a glance: the label
     * large, the policy method's own name in monospace under it. The cards write the
     * same state path the pull-down wrote, so the refusal below and everything that
     * reads the pair are untouched.
     *
     * @return array<int, ActionCards|Select>
     */
    public static function ability(): array
    {
        return [
            Select::make(self::SUBJECT)
                ->label(__('filament-bouncer::abilities.wizard.subject'))
                ->options(self::subjects())
                ->required()
                ->live(),
            ActionCards::make(self::ACTION)
                ->label(__('filament-bouncer::abilities.wizard.action'))
                ->helperText(__('filament-bouncer::abilities.wizard.actions_note'))
                ->options(static fn (Get $get): array => self::actions(self::text($get(self::SUBJECT))))
                ->columnSpanFull()
                ->required()
                ->live()
                // Without this the pair is only ever as good as the pull-downs, and a
                // request is not obliged to use them. Bouncer answers no to a rule nobody
                // ever declared and never says the name is unknown, so a row written from
                // an unresolvable pair would sit there for ever meaning nothing.
                ->rule(static fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                    if (! self::find(self::text($get(self::SUBJECT)), self::text($get(self::ACTION))) instanceof Ability) {
                        $fail(__('filament-bouncer::abilities.refusals.unknown'));
                    }
                }),
        ];
    }

    /**
     * @return array<int, Radio|TextInput>
     */
    public static function reach(): array
    {
        return [
            Radio::make(self::REACH)
                ->label(__('filament-bouncer::abilities.wizard.reach_field'))
                ->options(self::reaches())
                ->default(Reach::Owned->value)
                ->required()
                ->live()
                ->rule(static fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                    self::refuse($get, $fail);
                }),
            TextInput::make(self::RECORD)
                ->label(__('filament-bouncer::abilities.wizard.record'))
                ->helperText(__('filament-bouncer::abilities.wizard.record_note'))
                ->numeric()
                ->required(static fn (Get $get): bool => self::text($get(self::REACH)) === Reach::Record->value)
                ->visible(static fn (Get $get): bool => self::text($get(self::REACH)) === Reach::Record->value),
        ];
    }

    public static function title(): TextInput
    {
        return TextInput::make(self::TITLE)
            ->label(__('filament-bouncer::abilities.wizard.title'))
            ->helperText(__('filament-bouncer::abilities.form.title_note'))
            ->required()
            ->maxLength(150);
    }

    /**
     * The rule the choices add up to, in one sentence, recomposed as they are made.
     */
    public static function review(): Closure
    {
        return static function (Get $get): string {
            $ability = self::find(self::text($get(self::SUBJECT)), self::text($get(self::ACTION)));
            $reach = Reach::tryFrom(self::text($get(self::REACH)) ?? '') ?? Reach::All;

            return __('filament-bouncer::abilities.wizard.reading', [
                'rule' => $ability?->describe() ?? __('filament-bouncer::abilities.wizard.nothing'),
                'reach' => $reach === Reach::Record
                    ? __('filament-bouncer::abilities.reach.record_reading', ['id' => self::text($get(self::RECORD)) ?? ''])
                    : $reach->label(),
            ]);
        };
    }

    /**
     * The row the choices describe, taken from the catalogue.
     *
     * Nothing of the request reaches the two columns that decide what the row means. The
     * title does, because the title decides nothing.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public static function attributes(array $data): ?array
    {
        $ability = self::find(self::text($data[self::SUBJECT] ?? null), self::text($data[self::ACTION] ?? null));

        if (! $ability instanceof Ability) {
            return null;
        }

        $reach = Reach::tryFrom(self::text($data[self::REACH] ?? null) ?? '') ?? Reach::All;

        return [
            'name' => $ability->name,
            'entity_type' => $ability->entityMorphClass,
            'entity_id' => $reach === Reach::Record ? self::text($data[self::RECORD] ?? null) : null,
            'only_owned' => $reach === Reach::Owned,
            'title' => self::text($data[self::TITLE] ?? null) ?? $ability->title,
        ];
    }

    /**
     * Public because the live sentence above the wizard needs the same words the cards
     * offer, and a second spelling of the manage column's label is how the two would
     * come to disagree.
     *
     * @return array<string, string>
     */
    public static function actions(?string $key): array
    {
        $subject = $key === null ? null : app(CatalogRegistry::class)->current()->subject($key);

        if (! $subject instanceof Subject) {
            return [];
        }

        $labels = app(Labels::class);
        $actions = [];

        foreach (array_keys($subject->cells()) as $action) {
            $actions[$action] = $action === Ability::MANAGE_ACTION
                ? __('filament-bouncer::roles.form.manage')
                : $labels->action($action);
        }

        return $actions;
    }

    /**
     * The three refusals this screen owes the reader, in the order they matter.
     */
    private static function refuse(Get $get, Closure $fail): void
    {
        $reach = Reach::tryFrom(self::text($get(self::REACH)) ?? '');

        // A rule reaching everything is the plain one, and the plain one belongs to the
        // code that asks about it. Writing it here would put a row in front of `--check`
        // that no catalogue declares, and the next `--prune` would take it away again.
        if ($reach !== Reach::Owned && $reach !== Reach::Record) {
            $fail(__('filament-bouncer::abilities.refusals.narrow'));

            return;
        }

        $ability = self::find(self::text($get(self::SUBJECT)), self::text($get(self::ACTION)));

        if (! $ability instanceof Ability) {
            return;
        }

        if ($reach === Reach::Record && $ability->entityMorphClass === null) {
            $fail(__('filament-bouncer::abilities.refusals.record_needs_model'));

            return;
        }

        // Two rows saying the same thing are granted and cleared apart, and this screen
        // would only ever show one of them: whoever cleared the one they were shown would
        // walk away believing they had taken the rule back.
        if (self::exists($ability, $reach, self::text($get(self::RECORD)))) {
            $fail(__('filament-bouncer::abilities.refusals.duplicate'));
        }
    }

    private static function find(?string $subject, ?string $action): ?Ability
    {
        if ($subject === null || $action === null) {
            return null;
        }

        return app(CatalogRegistry::class)->current()->subject($subject)?->cells()[$action] ?? null;
    }

    /**
     * A null on either column reads as "is null" through Laravel's own `where`, which is
     * what makes one query answer for an ability about a model and one about nothing.
     */
    private static function exists(Ability $ability, Reach $reach, ?string $record): bool
    {
        return Models::ability()->newQuery()
            ->where('name', $ability->name)
            ->where('entity_type', $ability->entityMorphClass)
            ->where('entity_id', $reach === Reach::Record ? $record : null)
            ->where('only_owned', $reach === Reach::Owned)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    private static function subjects(): array
    {
        $subjects = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $key => $subject) {
            $subjects[$key] = $subject->label;
        }

        return $subjects;
    }

    /**
     * @return array<string, string>
     */
    private static function reaches(): array
    {
        $reaches = [];

        foreach (Reach::cases() as $reach) {
            $reaches[$reach->value] = $reach->label();
        }

        return $reaches;
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
