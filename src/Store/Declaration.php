<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * What the reconciliation has to say about one stored row, which is three things and
 * not two.
 *
 * Asked in two halves — whether the reconciliation speaks for the row at all, and only then
 * whether today's catalogue still declares it — because the same warning over a row on its way out
 * and over one that was never its business teaches people to ignore the warning.
 *
 * It stands in for a delete button on the abilities screen: which of the three a row is says more
 * about how it will end than a button would.
 */
enum Declaration: string
{
    case Declared = 'declared';

    case Drifted = 'drifted';

    case Apart = 'apart';

    public static function of(Model $ability): self
    {
        $store = app(AbilityStore::class);

        if (! $store->speaksFor($ability)) {
            return self::Apart;
        }

        $declared = [];

        foreach (app(CatalogRegistry::class)->current()->abilities() as $catalogued) {
            $declared[$catalogued->identity()] = true;
        }

        return isset($declared[$store->identity($ability)]) ? self::Declared : self::Drifted;
    }

    /**
     * Whether the row is on its way out.
     *
     * The one state in which the grid neither draws a row's cells nor writes them, asked here so
     * the two cannot disagree. They did once: the cells were withheld and the write was not, so a
     * request naming them anyway made grants the next `--prune` would take away silently.
     */
    public function isDoomed(): bool
    {
        return $this === self::Drifted;
    }

    public function label(): string
    {
        return __('filament-bouncer::abilities.declared.'.$this->value);
    }

    /**
     * The same answer at the length a record page has room for, for the two states that have
     * something to say.
     *
     * A row the reconciliation never spoke for has nothing to say: the badge already names the
     * state, and an explanation over a row in no danger is how people learn to skip the one over
     * a row that is.
     */
    public function note(): ?string
    {
        return $this === self::Apart
            ? null
            : (string) __('filament-bouncer::abilities.declared.'.$this->value.'_note');
    }

    public function color(): string
    {
        return match ($this) {
            self::Declared => 'success',
            self::Drifted => 'warning',
            self::Apart => 'gray',
        };
    }
}
