<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures;

/**
 * A navigation group named as a pure enum, which Filament accepts and a string does not
 * cover.
 */
enum ResourceGroup
{
    case Security;
}
