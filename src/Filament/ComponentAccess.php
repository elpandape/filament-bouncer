<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;

/**
 * Whether the person signed into the panel may reach a page or see a widget.
 *
 * The name is read off the catalogue and not composed here, which keeps one place deciding how an
 * ability is named and gives the ignore list its meaning for free: a component left out of the
 * catalogue has no ability to ask about, so it has to be open.
 */
final readonly class ComponentAccess
{
    public function __construct(
        private CatalogRegistry $catalogs,
        private Bouncer $bouncer,
    ) {}

    public function allows(string $component): bool
    {
        $ability = $this->catalogs->current()
            ->entity(Entity::keyFor($component))
            ?->ability(Ability::ACCESS_ACTION);

        if (! $ability instanceof Ability) {
            return true;
        }

        $authority = Filament::auth()->user();

        return $authority instanceof Model
            && $this->bouncer->getClipboard()->check($authority, $ability->name, $ability->entityType);
    }
}
