<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

enum AbilityScope: string
{
    case Read = 'read';

    case Write = 'write';

    case Withdraw = 'withdraw';

    case Irreversible = 'irreversible';

    /**
     * The scope an action falls into, according to the given map.
     *
     * Anything the map does not name is a write: an unclassified action is far more
     * likely to change something than to merely read it, and the grid has to err on
     * the side that makes a cell look more dangerous than it is, never less.
     *
     * @param  array<string, array<int, string>>  $map
     */
    public static function for(string $action, array $map): self
    {
        foreach (self::cases() as $scope) {
            if (in_array($action, $map[$scope->value] ?? [], true)) {
                return $scope;
            }
        }

        return self::Write;
    }

    /**
     * Where the scope sits when columns are laid out left to right.
     */
    public function order(): int
    {
        return match ($this) {
            self::Read => 0,
            self::Write => 1,
            self::Withdraw => 2,
            self::Irreversible => 3,
        };
    }

    /**
     * The colour its heading is tinted, so that the weight of a column can be seen
     * before it is read.
     */
    public function color(): string
    {
        return match ($this) {
            self::Read => 'gray',
            self::Write => 'primary',
            self::Withdraw => 'warning',
            self::Irreversible => 'danger',
        };
    }
}
