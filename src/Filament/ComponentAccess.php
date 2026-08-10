<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;

/**
 * Whether the person signed into the panel may reach a page or see a widget.
 *
 * The ability's name is read off the catalogue rather than composed here. That keeps the
 * one place that decides how an ability is named the only one, and it gives the ignore
 * list its meaning for free: a component the catalogue was told to leave out has no
 * ability to ask about, and a component nobody can be granted has to be open, or the
 * escape hatch would be a way of closing the door for good.
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
            ->subject(Subject::keyFor($component))
            ?->ability(Ability::ACCESS_ACTION);

        if (! $ability instanceof Ability) {
            return true;
        }

        $authority = Filament::auth()->user();

        return $authority instanceof Model
            && $this->bouncer->getClipboard()->check($authority, $ability->name, $ability->entityType);
    }
}
