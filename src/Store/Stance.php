<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use Filament\Support\Icons\Heroicon;

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

    /**
     * @return array<string, Heroicon>
     */
    public static function icons(): array
    {
        $icons = [];

        foreach (self::cases() as $stance) {
            $icons[$stance->value] = $stance->icon();
        }

        return $icons;
    }

    /**
     * A cell is one of as many columns as the catalogue has actions, so on any real
     * panel it is about a hundred pixels wide. Three words never fitted in that and
     * had to be stacked, which made the grid as tall as the catalogue is wide. Three
     * marks do fit, side by side, and the word goes to the label a screen reader
     * announces and a pointer reveals.
     */
    public function icon(): Heroicon
    {
        return match ($this) {
            self::Granted => Heroicon::Check,
            self::Neutral => Heroicon::Minus,
            self::Forbidden => Heroicon::XMark,
        };
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
