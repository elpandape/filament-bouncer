<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

/**
 * Every ability the panel is able to ask about, and nothing else.
 *
 * The catalogue is derived from code on every build and never read back from the
 * store, so an ability that no component checks cannot survive in it.
 */
final readonly class Catalog
{
    /**
     * @param  array<string, Entity>  $entities  keyed by entity key, in row order
     * @param  array<string, AbilityScope>  $actions  keyed by action, in column order
     */
    public function __construct(
        public array $entities,
        public array $actions,
    ) {}

    /**
     * @return array<int, Ability>
     */
    public function abilities(): array
    {
        $abilities = [];

        foreach ($this->entities as $entity) {
            foreach ($entity->abilities as $ability) {
                $abilities[] = $ability;
            }
        }

        return $abilities;
    }

    /**
     * The entities grouped the way the screen reads them, in tab order.
     *
     * Only the tabs that hold something come back, so a panel with no widgets never
     * grows an empty tab for them.
     *
     * @return array<string, array<string, Entity>> keyed by tab value, then entity key
     */
    public function tabs(): array
    {
        $tabs = [];

        foreach (CatalogTab::cases() as $tab) {
            $tabs[$tab->value] = [];
        }

        foreach ($this->entities as $key => $entity) {
            $tabs[$entity->kind->tab()->value][$key] = $entity;
        }

        return array_filter($tabs, static fn (array $entities): bool => $entities !== []);
    }

    public function entity(string $key): ?Entity
    {
        return $this->entities[$key] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->entities === [];
    }
}
