<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use Illuminate\Database\Eloquent\Model;

/**
 * How far a stored rule goes.
 *
 * Bouncer keeps the three apart in the same table: a rule about a whole model, a rule
 * about one record, and a rule about whatever its holder owns. The distinction is not
 * decoration — the reconciliation speaks for the first and never for the other two, and
 * the roles grid writes the first and never the other two — so the screen that composes
 * a rule has to name it, and the screen that lists rules has to read it back.
 */
enum Reach: string
{
    case All = 'all';

    case Owned = 'owned';

    case Record = 'record';

    public static function of(Model $ability): self
    {
        if ($ability->getAttribute('entity_id') !== null) {
            return self::Record;
        }

        return (bool) $ability->getAttribute('only_owned') ? self::Owned : self::All;
    }

    /**
     * The reach of a stored row, with the record named when there is one.
     *
     * The key is worth showing: two rows about the same action on the same model differ
     * in nothing else, and a list that drew them identically would be a list nobody can
     * act on.
     */
    public static function reading(Model $ability): string
    {
        $reach = self::of($ability);

        if ($reach !== self::Record) {
            return $reach->label();
        }

        /** @var int|string $id */
        $id = $ability->getAttribute('entity_id');

        return __('filament-bouncer::abilities.reach.record_reading', ['id' => (string) $id]);
    }

    public function label(): string
    {
        return __('filament-bouncer::abilities.reach.'.$this->value);
    }
}
