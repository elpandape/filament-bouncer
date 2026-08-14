<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use Illuminate\Database\Eloquent\Model;

/**
 * How a role stands against the whole catalogue, counted once.
 *
 * Counted once because four screens ask it, and each counting again would walk the catalogue four
 * times for a single row.
 *
 * What is reached without a rule of its own is carried apart: a role holding the wildcard holds no
 * row for any cell, so a figure drawn from the grants alone would report it can do nothing.
 */
final readonly class RoleCoverage
{
    public function __construct(
        public int $granted,
        public int $forbidden,
        public int $neutral,
        public int $total,
        public bool $reachesAll,
    ) {}

    public static function for(Model $role, Catalog $catalog): self
    {
        $abilities = app(RoleAbilities::class);
        $state = $abilities->toFormState($role);

        $granted = 0;
        $forbidden = 0;
        $total = 0;
        $reached = 0;

        foreach ($catalog->entities as $key => $entity) {
            foreach ($entity->cells() as $action => $ability) {
                $total++;

                $stance = Stance::tryFrom((string) ($state[$key][$action] ?? '')) ?? Stance::Neutral;

                if ($stance === Stance::Granted) {
                    $granted++;

                    continue;
                }

                if ($stance === Stance::Forbidden) {
                    $forbidden++;

                    continue;
                }

                if ($abilities->holds($role, $ability)) {
                    $reached++;
                }
            }
        }

        $neutral = $total - $granted - $forbidden;

        return new self(
            granted: $granted,
            forbidden: $forbidden,
            neutral: $neutral,
            total: $total,
            reachesAll: $reached > 0 && $reached === $neutral,
        );
    }
}
