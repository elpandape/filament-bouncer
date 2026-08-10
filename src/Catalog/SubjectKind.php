<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

enum SubjectKind: string
{
    case Resource = 'resource';

    case Model = 'model';

    case Page = 'page';

    case Widget = 'widget';

    case Custom = 'custom';

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
