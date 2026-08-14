<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use Illuminate\Database\Eloquent\Model;

/**
 * How a role stands against the whole catalogue, counted once.
 *
 * Four screens ask the same question — the grid's own summary, the table, the record page
 * and the header widget — and each of them counting it again would walk the catalogue
 * four times over for a single row.
 *
 * What is reached without a rule of its own is carried apart rather than folded into the
 * grants. A role holding the wildcard holds no row for any cell here, so a bar drawn from
 * the grants alone would report that it can do nothing at all, which is the opposite of
 * true.
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
