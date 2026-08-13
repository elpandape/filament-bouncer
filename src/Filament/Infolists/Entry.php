<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists;

use Filament\Infolists\Components\Entry as FilamentEntry;
use Illuminate\Database\Eloquent\Model;

/**
 * The one thing the entries of a role's record page share: knowing whether there is a role.
 *
 * An entry asked what it holds before it has been put in a schema throws — the container is
 * a typed property and nothing has assigned it yet — and a schema with no record answers
 * null. Both mean the same thing here, and writing that distinction out once per entry is
 * how one of them would end up different.
 */
abstract class Entry extends FilamentEntry
{
    protected function roleOrNull(): ?Model
    {
        $record = isset($this->container) ? $this->getRecord() : null;

        return $record instanceof Model ? $record : null;
    }
}
