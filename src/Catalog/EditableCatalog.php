<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;

/**
 * The part of the catalogue an authority is allowed to decide about.
 *
 * Nobody hands out what they do not hold themselves. That single rule is what keeps the
 * roles screen from being a way to grant yourself more than you were given, and it is
 * applied twice on purpose: once to decide what the screen offers, and again when the
 * screen is saved. The screen and the save read the same narrowed catalogue, so the two
 * cannot drift apart.
 */
final readonly class EditableCatalog
{
    public function __construct(
        private CatalogRegistry $catalogs,
        private Bouncer $bouncer,
    ) {}

    /**
     * The narrowed catalogue for whoever is signed into the panel right now.
     */
    public function current(): Catalog
    {
        $authority = Filament::auth()->user();

        return $this->for($this->catalogs->current(), $authority instanceof Model ? $authority : null);
    }

    public function for(Catalog $catalog, ?Model $authority): Catalog
    {
        if (! $authority instanceof Model) {
            return new Catalog([], []);
        }

        $clipboard = $this->bouncer->getClipboard();
        $subjects = [];
        $actions = [];

        foreach ($catalog->subjects as $key => $subject) {
            // An ability that has been forbidden reads as not held, which is the answer
            // that matters here: you cannot pass on something you have been denied.
            $held = array_filter(
                $subject->abilities,
                static fn (Ability $ability): bool => $clipboard->check($authority, $ability->name, $ability->entityType),
            );

            if ($held === []) {
                continue;
            }

            $manage = $subject->manage instanceof Ability
                && $clipboard->check($authority, $subject->manage->name, $subject->manage->entityType)
                    ? $subject->manage
                    : null;

            $subjects[$key] = new Subject($subject->key, $subject->label, $subject->kind, $subject->entityType, $held, $manage);

            $actions += array_intersect_key($catalog->actions, $held);
        }

        // Intersected against the full catalogue rather than sorted again, so that the
        // columns keep the order the catalogue laid them out in.
        return new Catalog($subjects, array_intersect_key($catalog->actions, $actions));
    }
}
