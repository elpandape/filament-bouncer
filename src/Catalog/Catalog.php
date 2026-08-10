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
     * @param  array<string, Subject>  $subjects  keyed by subject key, in row order
     * @param  array<string, AbilityScope>  $actions  keyed by action, in column order
     */
    public function __construct(
        public array $subjects,
        public array $actions,
    ) {}

    /**
     * @return array<int, Ability>
     */
    public function abilities(): array
    {
        $abilities = [];

        foreach ($this->subjects as $subject) {
            foreach ($subject->abilities as $ability) {
                $abilities[] = $ability;
            }
        }

        return $abilities;
    }

    /**
     * The subjects grouped the way the screen reads them, in tab order.
     *
     * Only the tabs that hold something come back, so a panel with no widgets never
     * grows an empty tab for them.
     *
     * @return array<string, array<string, Subject>> keyed by tab value, then subject key
     */
    public function tabs(): array
    {
        $tabs = [];

        foreach (CatalogTab::cases() as $tab) {
            $tabs[$tab->value] = [];
        }

        foreach ($this->subjects as $key => $subject) {
            $tabs[$subject->kind->tab()->value][$key] = $subject;
        }

        return array_filter($tabs, static fn (array $subjects): bool => $subjects !== []);
    }

    public function subject(string $key): ?Subject
    {
        return $this->subjects[$key] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->subjects === [];
    }
}
