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
 * Told nothing about a model it guesses a column named after whoever is asking, `user_id`,
 * and reads it through the model. Under `Model::shouldBeStrict()` that read throws for a
 * column the record does not carry, from inside a view, naming a column nobody ever wrote.
 * So the catch-all is registered last and answers no, which puts the guess out of reach:
 * Bouncer looks the class up first and only falls back when it finds nothing.
 *
 * A named model reads its column out of the attributes the record actually carries, so
 * one loaded without it answers no rather than throwing. Absence is an answer here, not a
 * mistake.
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
