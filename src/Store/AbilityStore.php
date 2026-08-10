<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

/**
 * The rows in Bouncer's ability table that the catalogue is allowed to speak for.
 */
final class AbilityStore
{
    /**
     * The name Bouncer gives an ability that stands for all of them.
     */
    public const string WILDCARD = '*';

    /**
     * The stored abilities the catalogue describes, keyed by identity.
     *
     * Three kinds of row are deliberately left out, because the catalogue does not
     * declare them and therefore must never offer to delete them: abilities about one
     * record, abilities restricted to what their holder owns, and the wildcards that
     * a blanket grant such as `everything()` creates.
     *
     * @return array<string, StoredAbility>
     */
    public function catalogued(): array
    {
        $abilities = [];

        foreach ($this->query()->get() as $ability) {
            $abilities[$this->identity($ability)] = $ability;
        }

        return $abilities;
    }

    /**
     * @param  array<array-key, Ability>  $abilities
     */
    public function create(array $abilities): void
    {
        if ($abilities === []) {
            return;
        }

        $now = now();

        Models::ability()->newQuery()->insert(array_map(
            static fn (Ability $ability): array => $ability->attributes() + [
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_values($abilities),
        ));
    }

    /**
     * Deletes abilities, and with them every grant that pointed at one.
     *
     * @param  array<array-key, StoredAbility>  $abilities
     */
    public function delete(array $abilities): void
    {
        if ($abilities === []) {
            return;
        }

        Models::ability()->newQuery()->whereKey(array_map(
            static fn (Model $ability): mixed => $ability->getKey(),
            array_values($abilities),
        ))->delete();
    }

    /**
     * Whether the reconciliation speaks for this row at all.
     *
     * Three answers hide behind one question on the screen, and only this one separates
     * them: a row the catalogue declares, a row it does not — which is drift, and which
     * `--prune` takes away — and a row that was never the catalogue's to declare. The
     * query is asked rather than the four conditions being spelled out a second time,
     * because a second spelling is how the two would come to disagree.
     */
    public function speaksFor(Model $ability): bool
    {
        return $this->query()->whereKey($ability->getKey())->exists();
    }

    /**
     * Whether the row narrows an ability to one record or to what its holder owns.
     *
     * These are two of the three kinds `catalogued()` leaves out, and the two the roles
     * grid cannot write: it matches the plain row and only the plain row, so a cell there
     * would report a stance it has no way to clear. Asked here because both screens need
     * to know when to stop going through the catalogue and write the row itself.
     */
    public function isRestricted(Model $ability): bool
    {
        if ($ability->getAttribute('entity_id') !== null) {
            return true;
        }

        return (bool) $ability->getAttribute('only_owned');
    }

    public function identity(Model $ability): string
    {
        /** @var string $name */
        $name = $ability->getAttribute('name');

        /** @var string|null $entityType */
        $entityType = $ability->getAttribute('entity_type');

        return Ability::identityFor($name, $entityType);
    }

    /**
     * @return Builder<StoredAbility>
     */
    private function query(): Builder
    {
        return Models::ability()->newQuery()
            ->whereNull('entity_id')
            ->where('only_owned', false)
            ->where('name', '!=', self::WILDCARD)
            ->where(static function (Builder $query): void {
                $query->whereNull('entity_type')->orWhere('entity_type', '!=', self::WILDCARD);
            });
    }
}
