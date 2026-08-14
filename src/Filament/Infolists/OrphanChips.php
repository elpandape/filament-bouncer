<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists;

use ElPandaPe\FilamentBouncer\Filament\Infolists\Concerns\ReadsDoomedRules;

/**
 * What the role is about to lose, worn as the chips the rest of the page is read in.
 *
 * It goes in the narrow column beside the metadata: a warning does not need the width of
 * the content, it needs to be where people look. Each rule is a chip carrying the raw
 * identifier and nothing else, grouped by the entity it points at, with a group of its own
 * for the ones pointing at none — a loose ability belongs to nobody, and pretending it
 * belongs to a model would hide it among that model's.
 *
 * It says there is nothing to lose when there is nothing to lose. Blank space reads as if
 * nobody had looked, and half the worth of a warning is knowing it was switched on.
 */
final class OrphanChips extends Entry
{
    use ReadsDoomedRules;

    protected string $view = 'filament-bouncer::roles.orphan-chips';

    /**
     * @return list<array{entity: string, actions: list<string>}>
     */
    public function getGroups(): array
    {
        $groups = [];

        foreach ($this->getDoomed() as $rule) {
            $entity = $rule['entity'] ?? __('filament-bouncer::roles.record.orphans_loose');

            $groups[$entity]['entity'] = $entity;
            $groups[$entity]['actions'][] = $rule['action'];
        }

        return array_values($groups);
    }

    public function getCount(): int
    {
        return count($this->getDoomed());
    }

    public function getHeadline(): string
    {
        return $this->isClean()
            ? __('filament-bouncer::roles.record.orphans_none')
            : __('filament-bouncer::roles.record.orphans_some');
    }

    public function getNote(): string
    {
        return $this->isClean()
            ? __('filament-bouncer::roles.record.orphans_note_none')
            : __('filament-bouncer::roles.record.orphans_note_some');
    }
}
