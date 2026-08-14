<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

/**
 * How the catalogue is divided on screen.
 *
 * An entity with a policy answers a handful of actions and belongs in a grid; a page, a widget or
 * an ability declared in configuration answers one, and a grid one column wide reads worse than a
 * list. The division is the difference between an entity that has actions and one with only a door.
 */
enum CatalogTab: string
{
    case Entities = 'entities';

    case Pages = 'pages';

    case Widgets = 'widgets';

    case Custom = 'custom';

    /**
     * Whether its entities are worth laying out as a grid of actions.
     */
    public function isGrid(): bool
    {
        return $this === self::Entities;
    }
}
