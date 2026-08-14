<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use ElPandaPe\FilamentBouncer\Exceptions\InvalidOwnership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * Who owns what, which Bouncer asks on every check it answers about a record — whether or
 * not a single ability was ever held down to what its holder owns.
 *
 * Told nothing about a model it guesses a `user_id` column and reads it through the model, which
 * under `Model::shouldBeStrict()` throws from inside a view naming a column nobody ever wrote. The
 * catch-all is registered last and answers no, putting the guess out of reach.
 *
 * A named model reads its column out of the attributes the record carries, so one loaded without it
 * answers no rather than throwing.
 */
final class Ownership
{
    public static function register(): void
    {
        foreach (Config::array('filament-bouncer.ownership') as $model => $column) {
            if (! is_string($model) || ! is_string($column)) {
                throw InvalidOwnership::of($model, $column);
            }

            Bouncer::ownedVia($model, static function (Model $record, Model $authority) use ($column): bool {
                $owner = $record->getAttributes()[$column] ?? null;
                $key = $authority->getKey();

                return is_scalar($owner) && is_scalar($key) && (string) $owner === (string) $key;
            });
        }

        Bouncer::ownedVia('*', static fn (): bool => false);
    }
}
