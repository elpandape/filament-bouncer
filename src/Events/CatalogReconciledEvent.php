<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * The catalogue was brought in line with what the panel's policies declare.
 *
 * A summary and not one event per row: a first run writes hundreds, and a listener that wrote
 * an audit entry for each would drown its own log.
 */
final readonly class CatalogReconciledEvent
{
    public function __construct(
        public int $written,
        public int $pruned,
        public ?Model $causer,
    ) {}
}
