<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

/**
 * The title Bouncer would compose, written into the form instead of only at save time.
 *
 * Both of Bouncer's models derive their title from their other attributes on the `creating` hook,
 * and only when one arrives null — `RoleTitle` from the name, `AbilityTitle` from the action, the
 * model, the record and "only owned". That leaves a blank that reads as "this will stay empty" and
 * forces a save to find out what came out. Here it is composed while it is being typed, with the
 * same generator, so what is on screen is exactly what will be stored.
 *
 * The generators never touch the database — they read those attributes and nothing else — so an
 * unsaved instance is all they need.
 *
 * The lock is what separates this from simply defaulting the field. A derived title and a
 * hand-written one read identically on screen: without it there is no way to tell which one is in
 * front of you, and no way back to the derived one once it has been overwritten.
 *
 * - `readOnly()` and not `disabled()`: a disabled field is not submitted, and this one has to reach
 *   the save even while it is still following the rest.
 * - The lock is a posture, not data, so it lives in the form state under `dehydrated(false)`.
 * - Opening a stored row works it out by comparing: a title that is not the one the generator would
 *   give was written by somebody, so that row opens unlocked and is never overwritten.
 */
final class DerivedTitle
{
    public const string FIELD = 'title';

    /**
     * Where the posture lives: still following the other fields, or taken over.
     */
    public const string CUSTOM = 'title_custom';

    /**
     * @param  Closure(Get): string  $fromState  the title the fields on screen compose right now
     * @param  Closure(Model): string  $fromRecord  the one a stored row's fields would compose
     * @return array<int, Hidden|TextInput>
     */
    public static function make(Closure $fromState, Closure $fromRecord): array
    {
        return [
            TextInput::make(self::FIELD)
                ->label(__('filament-bouncer::titles.label'))
                ->maxLength(150)
                ->readOnly(fn (Get $get): bool => ! $get(self::CUSTOM))
                ->helperText(fn (Get $get): string => $get(self::CUSTOM)
                    ? __('filament-bouncer::titles.custom')
                    : __('filament-bouncer::titles.derived'))
                ->hintAction(
                    Action::make('customiseTitle')
                        ->label(fn (Get $get): string => $get(self::CUSTOM)
                            ? __('filament-bouncer::titles.give_back')
                            : __('filament-bouncer::titles.take'))
                        ->icon(fn (Get $get): string => $get(self::CUSTOM) ? 'heroicon-m-arrow-uturn-left' : 'heroicon-m-pencil')
                        ->action(function (Get $get, Set $set) use ($fromState): void {
                            if ($get(self::CUSTOM)) {
                                $set(self::CUSTOM, false);
                                $set(self::FIELD, $fromState($get));

                                return;
                            }

                            $set(self::CUSTOM, true);
                        }),
                ),

            // Declared with `columnSpan('hidden')` in its own `setUp()`, so it takes no cell of the
            // grid and can sit beside the field inside the same section.
            Hidden::make(self::CUSTOM)
                ->dehydrated(false)
                ->afterStateHydrated(function (Set $set, ?Model $record) use ($fromRecord): void {
                    $set(self::CUSTOM, $record instanceof Model && self::wasWrittenByHand($record, $fromRecord));
                }),
        ];
    }

    /**
     * What every field the title depends on does when it changes.
     *
     * @param  Closure(Get): string  $fromState
     * @return Closure(Get, Set): void
     */
    public static function follow(Closure $fromState): Closure
    {
        return function (Get $get, Set $set) use ($fromState): void {
            if ($get(self::CUSTOM)) {
                return;
            }

            $set(self::FIELD, $fromState($get));
        };
    }

    /**
     * @param  Closure(Model): string  $fromRecord
     */
    private static function wasWrittenByHand(Model $record, Closure $fromRecord): bool
    {
        $title = $record->getAttribute(self::FIELD);

        return is_string($title) && filled($title) && $title !== $fromRecord($record);
    }
}
