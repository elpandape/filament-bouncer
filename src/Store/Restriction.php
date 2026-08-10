<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

/**
 * What a role holds about an ability beyond the plain row the grid writes.
 *
 * Bouncer keeps a grant over a whole model, a grant over one record and a grant over
 * what the holder owns in three separate rows. The grid writes the first and must not
 * touch the other two — but staying silent about them would be its own kind of lie:
 * the cell would read "not granted" about a role that can plainly delete its own posts.
 */
final readonly class Restriction
{
    public function __construct(
        public bool $owned = false,
        public int $records = 0,
    ) {}

    public function withOwned(): self
    {
        return new self(true, $this->records);
    }

    public function withRecord(): self
    {
        return new self($this->owned, $this->records + 1);
    }
}
