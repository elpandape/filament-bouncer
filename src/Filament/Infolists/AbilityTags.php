<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists;

use ElPandaPe\FilamentBouncer\Catalog\Subject;
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
 * A grid answers cell by cell and so has to draw them all: over a catalogue of four
 * subjects that is nearly thirty boxes to answer three things, and the three that matter
 * read as faintly as the twenty-odd that say nothing. Here a row carries only the actions
 * holding a stance, so the answer is read at a glance and its length grows with what the
 * role does rather than with what the panel declares — which is what saves it from the
 * grid, whose columns are the union of whatever any policy declares and grow on their own.
 *
 * What it loses is just as real and worth knowing: without a grid nobody can ask "and
 * creating?" and find the box, because an action with no stance simply is not there. The
 * foot makes up for half of that by naming the subjects it says nothing about; what this
 * page can no longer say is which actions existed and went unused. The form is for that,
 * and it draws them all because there they have to be settable.
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
     * A row per subject the role says something about, its actions in catalogue order so
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
     * A page, a widget or an ability declared in configuration answers one action, so its
     * row is the tag and nothing else: there is no list to trim. They go apart from the
     * subjects because their action is none of the ones the others declare, and mixing
     * them would suggest they share it.
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
     * They live in a block of their own inside the abilities, and not in a section apart,
     * because they are the same as the rest with a fence around them: the same action over
     * the same subject, reaching one record or only what its holder owns. What sets them
     * apart is who writes them — they are composed by hand on the abilities screen and the
     * grid does not touch them — not what they are about.
     *
     * The record is named rather than counted: "over 1 record" does not say which, and
     * with that nobody can review the rule or take it away. If the record is gone that is
     * said too, because the rule is still there pointing at nothing and no screen gives it
     * away.
     *
     * @return list<array{subject: string, action: string, label: string, owned: bool, records: list<array{title: string, missing: bool}>}>
     */
    public function getNarrowed(): array
    {
        $record = $this->roleOrNull();

        if (! $record instanceof Model) {
            return [];
        }

        $labels = app(Labels::class);
        $subjects = array_column($this->gridRows(), 'label', 'key');
        $narrowed = [];

        foreach ($this->narrowedRows($record) as $row) {
            /** @var string $name */
            $name = $row->getAttribute('name');

            /** @var string|null $type */
            $type = $row->getAttribute('entity_type');

            $owned = (bool) $row->getAttribute('only_owned');

            $key = $name.'|'.($type ?? '').'|'.($owned ? '1' : '0');

            $narrowed[$key] ??= [
                'subject' => $type === null ? '—' : ($subjects[Subject::keyFor($type)] ?? $type),
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
     * The subjects and doors the role says nothing about, split by their group.
     *
     * They are named rather than simply left out: a row missing because the role is silent
     * and a row missing because the panel does not declare it are two different things and
     * would read the same on screen. What changes with the size of the catalogue is *how*
     * they are named, not whether they are.
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
     * do the disclosure's job while taking the room it saves. "Subjects" covers them all:
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
     * It is asked of the panel's resource, which already knows how to title a row of that
     * model; with no resource the key is left, which at least identifies. A deleted record
     * leaves the rule standing and pointing at nothing, so that is said rather than hidden.
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
     * The pivot is joined by hand rather than read through the relation because the role
     * model is whichever the application configured, and nothing promises the analyser it
     * carries Bouncer's traits. Both tables have an `entity_id` column and they mean
     * different things — one is the role, the other the narrowed record — so they are
     * prefixed.
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
     * A subject's tags: the actions holding a stance, and none other.
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
