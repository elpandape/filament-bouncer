<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Exceptions;

use InvalidArgumentException;

/**
 * Thrown while the application boots, because a mangled entry here fails towards letting
 * people through: the model would fall to the catch-all, answer that nobody owns it, and
 * every ability held down to what its holder owns would quietly stop matching.
 */
final class InvalidOwnership extends InvalidArgumentException
{
    public static function of(mixed $model, mixed $column): self
    {
        return new self(sprintf(
            'The "ownership" key of the filament-bouncer configuration takes a model class name for a key and a column name for a value; got %s => %s.',
            get_debug_type($model),
            get_debug_type($column),
        ));
    }
}
