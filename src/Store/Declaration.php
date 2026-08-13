<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * What the reconciliation has to say about one stored row, which is three things and
 * not two.
 *
 * Reading the same warning over a row that is about to be swept away and over a row that
 * was never the reconciliation's business teaches people to ignore the warning. So the
 * question is asked in two halves: whether the reconciliation speaks for the row at all,
 * and — only then — whether today's catalogue still declares it.
 *
 * This is what stands in for a delete button on the abilities screen. A row goes when the
 * code stops declaring it and `--prune` takes it, and knowing which of the three a row is
 * says far more about how it will end than a button that would take every grant pointing
 * at it along in one click.
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
     * This is the one state in which the abilities screen neither draws a row's cells nor
     * writes them, and it is asked here so that the drawing and the writing cannot come
     * to disagree. They did: the cells were withheld and the write was not, so a request
     * that named them anyway made grants the next `--prune` would take away along with
     * the row — silently, which is the loss the whole screen exists to make visible.
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
     * A row the reconciliation never spoke for has nothing: its note said that `--check` does not
     * fail on it and `--prune` does not take it, which is a sentence about two things that are not
     * going to happen. The badge already names the state, and reading an explanation over a row in
     * no danger is how people learn to skip the one over a row that is.
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
