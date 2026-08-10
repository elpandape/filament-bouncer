<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

/**
 * What a role says about an ability.
 *
 * The middle case is not a quieter way of saying no. A role that stays neutral leaves the
 * answer to whatever else the person holds; a role that forbids overrules all of it,
 * including a grant from another role and one made straight to the user. That is the one
 * thing Bouncer does which `spatie/laravel-permission` cannot express, and the reason this
 * package exists at all.
 */
enum Stance: string
{
    case Granted = 'granted';

    case Neutral = 'neutral';

    case Forbidden = 'forbidden';

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        $colors = [];

        foreach (self::cases() as $stance) {
            $colors[$stance->value] = $stance->color();
        }

        return $colors;
    }

    public function color(): string
    {
        return match ($this) {
            self::Granted => 'success',
            self::Neutral => 'gray',
            self::Forbidden => 'danger',
        };
    }
}
