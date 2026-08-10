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
     * The stored row for one catalogued ability, if the reconciliation has written it.
     */
    public function find(string $name, ?string $entityMorphClass): ?StoredAbility
    {
        $query = $this->query()->where('name', $name);

        $entityMorphClass === null
            ? $query->whereNull('entity_type')
            : $query->where('entity_type', $entityMorphClass);

        return $query->first();
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
