<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * What the Gate answers about this rule right now, before it is handed out.
 *
 * Holding a rule and being allowed it are not the same thing: a denial from another role wins, the
 * wildcard grants what nobody wrote down, and a fenced rule only answers about the record it fences.
 *
 * It asks Bouncer's clipboard and not the Gate, which would resolve this very model's policy and
 * ask it the same question for ever. There is no submit button, since nothing is written.
 *
 * `answers()`, `reading()` and `tone()` are public and decide with no screen in the way: the markup
 * of an action's modal does not travel in its component's, so a verdict left inside the closures
 * would be one no test could check.
 */
final class ProbeAbility
{
    public const string HOLDER = 'holder';

    public const string RECORD = 'record';

    public static function make(): Action
    {
        return Action::make('probe')
            ->label(__('filament-bouncer::abilities.probe.label'))
            ->icon('heroicon-m-beaker')
            ->color('gray')
            ->modalHeading(__('filament-bouncer::abilities.probe.heading'))
            ->modalDescription(__('filament-bouncer::abilities.probe.note'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament-bouncer::abilities.probe.close'))
            ->schema(fn (Model $record): array => self::questions($record));
    }

    /**
     * What the Gate answers, or null while it cannot be asked.
     *
     * Null when nobody has been chosen, and when the rule names a model that does not load: an
     * unloadable class cannot be handed to Bouncer — it tries to resolve it and dies — so that one
     * is answered here rather than there.
     */
    public static function answers(Model $ability, ?string $holder, ?string $record): ?bool
    {
        $authority = self::authority($holder);

        if (! $authority instanceof Model || ! self::isAskable($ability)) {
            return null;
        }

        /** @var string $name */
        $name = $ability->getAttribute('name');

        return Bouncer::getClipboard()->check($authority, $name, self::entity($ability, $record));
    }

    /**
     * A rule naming a model that cannot be loaded cannot be asked about, and saying so is worth
     * more than offering a pull-down whose answer would always be the same for the wrong reason.
     */
    public static function isAskable(Model $ability): bool
    {
        $type = $ability->getAttribute('entity_type');

        return ! is_string($type) || $type === '*' || self::modelOf($type) !== null;
    }

    /**
     * How the verdict reads, and in what colour.
     *
     * Both are public and take the verdict already worked out rather than resolving it themselves:
     * inside the modal's closures no test reaches them, and a branch of paint nobody runs is a
     * branch that can say "can" in red without anything noticing.
     */
    public static function reading(?bool $allowed): string
    {
        return match ($allowed) {
            true => __('filament-bouncer::abilities.probe.yes'),
            false => __('filament-bouncer::abilities.probe.no'),
            null => __('filament-bouncer::abilities.probe.choose'),
        };
    }

    public static function tone(?bool $allowed): string
    {
        return match ($allowed) {
            true => 'success',
            false => 'danger',
            null => 'gray',
        };
    }

    /**
     * @return array<int, Select|Text|TextInput>
     */
    private static function questions(Model $ability): array
    {
        if (! self::isAskable($ability)) {
            return [
                Text::make(__('filament-bouncer::abilities.probe.unaskable'))->color('danger'),
            ];
        }

        return [
            Select::make(self::HOLDER)
                ->label(__('filament-bouncer::abilities.probe.holder'))
                ->options(self::holders())
                ->searchable()
                ->live(),

            TextInput::make(self::RECORD)
                ->label(__('filament-bouncer::abilities.probe.record'))
                ->numeric()
                ->live(onBlur: true)
                ->default(self::text($ability->getAttribute('entity_id')))
                ->helperText(__('filament-bouncer::abilities.probe.record_note'))
                // With no model there is no record to ask about: the rule is a loose ability and
                // the Gate answers it with no entity.
                ->visible(fn (): bool => is_string($ability->getAttribute('entity_type'))),

            Text::make(fn (Get $get): string => self::reading(self::verdict($ability, $get)))
                ->size(TextSize::Large)
                ->color(fn (Get $get): string => self::tone(self::verdict($ability, $get))),
        ];
    }

    private static function verdict(Model $ability, Get $get): ?bool
    {
        return self::answers($ability, self::text($get(self::HOLDER)), self::text($get(self::RECORD)));
    }

    /**
     * The roles and the accounts, in two groups.
     *
     * Both are authorities to Bouncer, and keeping them apart is what stops "this role grants"
     * being read as "this person can" — the difference the probe exists to show.
     *
     * @return array<string, array<string, string>>
     */
    private static function holders(): array
    {
        $roles = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var string $name */
            $name = $role->getAttribute('name');
            $roles[self::handle('role', $role)] = $name;
        }

        $accounts = [];

        foreach (Models::user()->newQuery()->limit(50)->get() as $account) {
            $accounts[self::handle('user', $account)] = self::text($account->getAttribute('name'))
                ?? self::handle('user', $account);
        }

        return array_filter([
            __('filament-bouncer::abilities.probe.roles') => $roles,
            __('filament-bouncer::abilities.probe.accounts') => $accounts,
        ], static fn (array $group): bool => $group !== []);
    }

    private static function authority(?string $holder): ?Model
    {
        if ($holder === null) {
            return null;
        }

        [$kind, $key] = array_pad(explode(':', $holder, 2), 2, null);

        if ($key === null) {
            return null;
        }

        return $kind === 'role'
            ? Models::role()->newQuery()->find($key)
            : Models::user()->newQuery()->find($key);
    }

    /**
     * What is asked about: the record where one was named, the model where not, and nothing where
     * the rule speaks of no model.
     */
    private static function entity(Model $ability, ?string $record): Model|string|null
    {
        $type = $ability->getAttribute('entity_type');

        if (! is_string($type)) {
            return null;
        }

        $class = self::modelOf($type);

        if ($class === null) {
            return $type;
        }

        return $record === null ? $class : ($class::query()->find($record) ?? $class);
    }

    /**
     * @return class-string<Model>|null
     */
    private static function modelOf(string $type): ?string
    {
        /** @var class-string<Model>|null $class */
        $class = Relation::getMorphedModel($type) ?? (is_a($type, Model::class, true) ? $type : null);

        return $class;
    }

    /**
     * How an authority is named in the pull-down: its kind and its key.
     */
    private static function handle(string $kind, Model $model): string
    {
        $key = $model->getKey();

        return $kind.':'.(is_scalar($key) ? (string) $key : '');
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && filled($value) ? (string) $value : null;
    }
}
