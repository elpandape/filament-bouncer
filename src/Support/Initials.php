<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

/**
 * The letters an avatar circle wears when there is no picture to wear.
 */
final class Initials
{
    public static function of(string $name): string
    {
        $words = preg_split('/\s+/', mb_trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials === '' ? '#' : $initials;
    }
}
