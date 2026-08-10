<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Exceptions;

use LogicException;

/**
 * Thrown while a panel is booting, by design, and in production too.
 *
 * A page or widget that decides nothing is open to everybody who reaches the panel at
 * all. That is a hole nobody discovers by looking, so it is turned into a failure loud
 * enough to be reverted within the hour.
 */
final class UnguardedComponent extends LogicException
{
    public static function page(string $component): self
    {
        return new self(sprintf(
            '[%s] does not decide who may reach it. Use the AuthorizesPage trait, override canAccess(), or name the page in the "ignore" key of the filament-bouncer configuration.',
            $component,
        ));
    }

    public static function widget(string $component): self
    {
        return new self(sprintf(
            '[%s] does not decide who may see it. Use the AuthorizesWidget trait, override canView(), or name the widget in the "ignore" key of the filament-bouncer configuration.',
            $component,
        ));
    }
}
