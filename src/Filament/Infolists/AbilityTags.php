<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists;

use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Filament\Infolists\Concerns\ReadsStances;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

/**
 * What the role says, and only what it says.
 *
 * A row carries only the actions holding a stance, so its length grows with what the role does
 * rather than with what the panel declares — where a grid has to draw every cell, and the three
 * that matter read as faintly as the twenty-odd that say nothing.
 *
 * The loss is real: an action with no stance is simply not there, so nobody can ask "and creating?"
 * and find the box. The foot makes up half of it by naming the entities this says nothing about;
 * the form draws them all, because there they have to be settable.
 */
final class AbilityTags extends Entry
{
    use ReadsStances;

    /**
     * How many names the foot spells out before listing them stops informing.
     *
     * Past this the list says what a number says and takes a paragraph doing it: over a
     * panel with thirty models, a read-only role would end up with sixty names down
     * there — and the *less* the role says, the longer it gets, which is exactly what this
     * entry exists not to do.
     */
    private const int SPELLED = 6;

    protected string $view = 'filament-bouncer::roles.ability-tags';

    /**
     * A row per entity the role says something about, its actions in catalogue order so
     * that no two rows sort the same thing differently.
     *
     * @return list<array{key: string, label: string, policy: string|null, icon: string|null, tags: list<array{action: string, label: string, stance: string}>}>
     */
    public function getRows(): array
    {
        $order = $this->actionOrder();
        $rows = [];

        foreach ($this->gridRows() as $row) {
            $tags = $this->tagsFor($row['key'], array_values(array_filter($order, static fn (string $action): bool => isset($row['cells'][$action]))));

            if ($tags !== []) {
                $rows[] = [
                    'key' => $row['key'],
                    'label' => $row['label'],
                    'policy' => $row['policy'],
                    'icon' => $row['icon'],
                    'tags' => $tags,
                ];
            }
        }

        return $rows;
    }

    /**
     * The doors holding a stance, grouped by their tab.
     *
     * They go apart from the entities because their one action is none of the ones the others
     * declare, and mixing them would suggest they share it.
     *
     * @return list<array{tab: string, label: string, rows: list<array{key: string, label: string, tags: list<array{action: string, label: string, stance: string}>}>}>
     */
    public function getDoors(): array
    {
        $doors = [];

        foreach ($this->grid()->getSections() as $tab => $section) {
            if ($section['grid']) {
                continue;
            }

            $rows = [];

            foreach ($section['rows'] as $row) {
                $tags = $this->tagsFor($row['key'], [(string) $row['action']]);

                if ($tags !== []) {
                    $rows[] = ['key' => $row['key'], 'label' => $row['label'], 'tags' => $tags];
                }
            }

            if ($rows !== []) {
                $doors[] = ['tab' => $tab, 'label' => $section['label'], 'rows' => $rows];
            }
        }

        return $doors;
    }

    /**
     * The narrowed rules, grouped by the ability they hem in.
     *
     * Inside the abilities and not in a section apart: they are the same action over the same
     * entity with a fence around it, and what sets them apart is who writes them.
     *
     * The record is named rather than counted — "over 1 record" does not say which — and a
     * record that is gone is said to be gone, since the rule still points at it.
     *
     * @return list<array{entity: string, action: string, label: string, owned: bool, records: list<array{title: string, missing: bool}>}>
     */
    public function getNarrowed(): array
    {
        $record = $this->roleOrNull();

        if (! $record instanceof Model) {
            return [];
        }

        $labels = app(Labels::class);
        $entities = array_column($this->gridRows(), 'label', 'key');
        $narrowed = [];

        foreach ($this->narrowedRows($record) as $row) {
            /** @var string $name */
            $name = $row->getAttribute('name');

            /** @var string|null $type */
            $type = $row->getAttribute('entity_type');

            $owned = (bool) $row->getAttribute('only_owned');

            $key = $name.'|'.($type ?? '').'|'.($owned ? '1' : '0');

            $narrowed[$key] ??= [
                'entity' => $type === null ? '—' : ($entities[Entity::keyFor($type)] ?? $type),
                'action' => $name,
                'label' => $labels->action($name),
                'owned' => $owned,
                'records' => [],
            ];

            /** @var int|string|null $id */
            $id = $row->getAttribute('entity_id');

            if ($id !== null && $type !== null) {
                $narrowed[$key]['records'][] = $this->titleFor($type, $id);
            }
        }

        return array_values($narrowed);
    }

    /**
     * The entities and doors the role says nothing about, split by their group.
     *
     * Named rather than left out: a row missing because the role is silent and one missing
     * because the panel does not declare it would read the same. The size of the catalogue
     * changes how they are named, not whether they are.
     *
     * @return list<array{tab: string, label: string, names: list<string>}>
     */
    public function getSilentGroups(): array
    {
        $said = [
            ...array_column($this->getRows(), 'key'),
            ...array_column(array_merge([], ...array_column($this->getDoors(), 'rows')), 'key'),
        ];

        $groups = [];

        foreach ($this->grid()->getSections() as $tab => $section) {
            $names = array_column(array_filter(
                $section['rows'],
                static fn (array $row): bool => ! in_array($row['key'], $said, true),
            ), 'label');

            if ($names !== []) {
                $groups[] = ['tab' => $tab, 'label' => $section['label'], 'names' => $names];
            }
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    public function getSilent(): array
    {
        return array_merge([], ...array_column($this->getSilentGroups(), 'names'));
    }

    public function spellsSilent(): bool
    {
        return count($this->getSilent()) <= self::SPELLED;
    }

    /**
     * The foot: the names while they fit and, the moment they do not, how many there are.
     *
     * The figure can stand alone because the detail is not lost — the whole list is right
     * below, split by group and folded away — and a line already splitting by group would
     * do the disclosure's job while taking the room it saves. "Entities" covers them all:
     * in the catalogue a model, a page and a loose ability are equally one.
     */
    public function getSilentLabel(): string
    {
        return $this->spellsSilent()
            ? __('filament-bouncer::roles.record.silent_spelled', ['names' => $this->enumerate($this->getSilent())])
            : __('filament-bouncer::roles.record.silent_counted', ['count' => count($this->getSilent())]);
    }

    /**
     * @param  list<string>  $items
     */
    private function enumerate(array $items): string
    {
        $last = array_pop($items) ?? '';

        return $items === [] ? $last : implode(', ', $items).' '.__('filament-bouncer::roles.record.and').' '.$last;
    }

    /**
     * What the record a rule points at is called.
     *
     * Asked of the panel's resource, which already knows how to title a row of that model; with
     * no resource the key at least identifies. A deleted record leaves the rule pointing at
     * nothing, so that is said rather than hidden.
     *
     * @return array{title: string, missing: bool}
     */
    private function titleFor(string $type, int|string $id): array
    {
        /** @var class-string<Model> $class */
        $class = Relation::getMorphedModel($type) ?? $type;

        $record = $class::query()->find($id);

        if (! $record instanceof Model) {
            return ['title' => '#'.$id, 'missing' => true];
        }

        $resource = Filament::getModelResource($class);
        $title = $resource !== null && is_subclass_of($resource, Resource::class) ? $resource::getRecordTitle($record) : null;

        return [
            'title' => $title instanceof Htmlable ? $title->toHtml() : ($title ?? '#'.$id),
            'missing' => false,
        ];
    }

    /**
     * The ability rows of this role carrying a fence.
     *
     * The pivot is joined by hand because the role model is whichever the application
     * configured, and nothing promises the analyser it carries Bouncer's traits. Both tables
     * have an `entity_id` meaning different things, so they are prefixed.
     *
     * @return EloquentCollection<int, StoredAbility>
     */
    private function narrowedRows(Model $record): EloquentCollection
    {
        $abilities = Models::table('abilities');
        $permissions = Models::table('permissions');

        return Models::ability()->newQuery()
            ->join($permissions, $permissions.'.ability_id', '=', $abilities.'.id')
            ->where($permissions.'.entity_id', $record->getKey())
            ->where($permissions.'.entity_type', $record->getMorphClass())
            ->where(static function (Builder $query) use ($abilities): void {
                $query->whereNotNull($abilities.'.entity_id')->orWhere($abilities.'.only_owned', true);
            })
            ->orderBy($abilities.'.name')
            ->get([$abilities.'.*']);
    }

    /**
     * An entity's tags: the actions holding a stance, and none other.
     *
     * @param  list<string>  $actions
     * @return list<array{action: string, label: string, stance: string}>
     */
    private function tagsFor(string $key, array $actions): array
    {
        $labels = app(Labels::class);
        $stances = $this->getStances();
        $tags = [];

        foreach ($actions as $action) {
            $stance = $stances[$key][$action] ?? Stance::Neutral->value;

            if ($stance !== Stance::Neutral->value) {
                $tags[] = ['action' => $action, 'label' => $labels->action($action), 'stance' => $stance];
            }
        }

        return $tags;
    }
}
