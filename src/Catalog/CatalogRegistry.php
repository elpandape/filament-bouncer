<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

use Filament\Panel;

/**
 * Holds the catalogue of each panel for as long as the request lasts.
 *
 * Building one walks every resource, page and widget and reflects over every policy.
 * A Livewire request renders its component more than once, so without this the same
 * walk would run several times to produce the same answer.
 */
final class CatalogRegistry
{
    /**
     * @var array<string, Catalog>
     */
    private array $catalogs = [];

    public function __construct(private readonly CatalogBuilder $builder) {}

    public function get(Panel $panel): Catalog
    {
        return $this->catalogs[$panel->getId()] ??= $this->builder->build($panel);
    }

    public function forget(): void
    {
        $this->catalogs = [];
    }
}
