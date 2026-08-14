<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

enum EntityKind: string
{
    case Resource = 'resource';

    case Model = 'model';

    case Page = 'page';

    case Widget = 'widget';

    case Custom = 'custom';

    /**
     * The part of the screen the kind is read in.
     *
     * Resources and models share one, because both answer a policy and so both have
     * columns to fill; the rest have a single action each and are read as lists.
     */
    public function tab(): CatalogTab
    {
        return match ($this) {
            self::Resource, self::Model => CatalogTab::Entities,
            self::Page => CatalogTab::Pages,
            self::Widget => CatalogTab::Widgets,
            self::Custom => CatalogTab::Custom,
        };
    }

    /**
     * Where the kind sits when rows are laid out top to bottom.
     */
    public function order(): int
    {
        return match ($this) {
            self::Resource => 0,
            self::Model => 1,
            self::Page => 2,
            self::Widget => 3,
            self::Custom => 4,
        };
    }
}
