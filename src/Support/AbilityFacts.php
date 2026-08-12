<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\Reach;
use Illuminate\Database\Eloquent\Model;

/**
 * One stored rule, read out in the words the screens draw it in.
 *
 * Three screens compose the same sentence about a row — the hero above the record
 * pages, the entries of the definition card, and the read-only facts on the changing
 * screen — and this is the one place the words come from, so the three cannot drift
 * into telling the same row differently.
 */
final readonly class AbilityFacts
{
    private function __construct(
        public string $actionLabel,
        public string $actionName,
        public string $subjectLabel,
        public ?string $subjectClass,
        public Reach $reach,
        public string $reachReading,
        public ?string $entityId,
    ) {}

    public static function of(Model $ability): self
    {
        /** @var string $name */
        $name = $ability->getAttribute('name');

        $type = $ability->getAttribute('entity_type');
        $type = is_string($type) ? $type : null;

        $subject = $type === null
            ? null
            : app(CatalogRegistry::class)->current()->subject(Subject::keyFor($type));

        /** @var int|string|null $entityId */
        $entityId = $ability->getAttribute('entity_id');

        return new self(
            actionLabel: $name === AbilityStore::WILDCARD
                ? __('filament-bouncer::roles.form.manage')
                : app(Labels::class)->action($name),
            actionName: $name,
            subjectLabel: $subject->label ?? $type ?? __('filament-bouncer::abilities.form.no_entity'),
            subjectClass: $subject instanceof Subject ? $type : null,
            reach: Reach::of($ability),
            reachReading: Reach::reading($ability),
            entityId: $entityId === null ? null : (string) $entityId,
        );
    }

    /**
     * The colour a reach badge wears: the plain rule in grey, either narrowing in the
     * informational blue the approved design gives them.
     */
    public function reachColor(): string
    {
        return $this->reach === Reach::All ? 'gray' : 'info';
    }
}
