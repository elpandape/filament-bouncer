<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

/**
 * How the catalogue is divided on screen.
 *
 * A subject that carries a policy answers a handful of actions and belongs in a grid;
 * a page, a widget or an ability declared in configuration answers exactly one, and a
 * grid one column wide is a worse way to read a list than a list is. The division is
 * therefore not decoration: it is the difference between a subject that has actions
 * and one that only has a door.
 */
enum CatalogTab: string
{
    case Subjects = 'subjects';

    case Pages = 'pages';

    case Widgets = 'widgets';

    case Custom = 'custom';

    /**
     * Whether its subjects are worth laying out as a grid of actions.
     */
    public function isGrid(): bool
    {
        return $this === self::Subjects;
    }
}
