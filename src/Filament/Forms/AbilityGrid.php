<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogTab;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The whole catalogue as one field, laid out as sections of rows.
 *
 * A view of the package's own because `ToggleButtons` would need a field per cell: thirty resources
 * would mount a hundred and eighty Livewire components and spend a request on every click. The
 * nested state — entity, action, stance — is the shape the store reads and writes, so rewriting the
 * view cannot break the save.
 *
 * The columns are the union of the actions any policy declares, so they grow on their own; the
 * entity column is pinned and the table scrolls, since a row whose entity has left the screen
 * cannot be read.
 */
final class AbilityGrid extends Field
{
    protected string $view = 'filament-bouncer::forms.ability-matrix';

    private Catalog $catalog;

    private ?Closure $notes = null;

    public function catalog(Catalog $catalog): static
    {
        $this->catalog = $catalog;

        // A role that holds nothing says nothing about anything, and it is the field that
        // knows what "nothing" looks like — a screen asked to remember would eventually
        // forget, and a missing cell is a cell nobody can write.
        $this->default($this->neutralState($catalog));

        return $this;
    }

    /**
     * Refuse a state that says nothing about anything.
     *
     * Only the screen that composes a role asks for this. A role with every cell on
     * neutral grants nothing, forbids nothing and answers no question — there is nothing
     * about it worth writing down. Editing is different on purpose: clearing everything
     * back to neutral is how what a role holds is taken away.
     */
    public function requiresAStance(): static
    {
        $this->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
            foreach (is_array($value) ? $value : [] as $actions) {
                foreach (is_array($actions) ? $actions : [] as $stance) {
                    if ($stance === Stance::Granted->value || $stance === Stance::Forbidden->value) {
                        return;
                    }
                }
            }

            $fail(__('filament-bouncer::roles.form.requires_stance'));
        });

        return $this;
    }

    /**
     * What each row says beyond its stance, worked out from the record being edited.
     */
    public function notes(Closure $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * The catalogue, ready to draw: a section per tab, a row per entity.
     *
     * Only the first tab is a grid: a page, a widget or an ability declared in configuration
     * answers one action, and a grid one column wide reads worse than a list.
     *
     * @return array<string, array{label: string, grid: bool, rows: list<array{key: string, label: string, class: string|null, policy: string|null, icon: string|null, action: string|null, cells: array<string, bool>}>}>
     */
    public function getSections(): array
    {
        /** @var array<string, string> $icons */
        $icons = config('filament-bouncer.icons', []);

        $sections = [];

        foreach ($this->catalog->tabs() as $value => $entities) {
            $tab = CatalogTab::from((string) $value);
            $rows = [];

            foreach ($entities as $key => $entity) {
                $cells = array_fill_keys(array_keys($entity->cells()), true);

                $rows[] = [
                    'key' => $key,
                    'label' => $entity->label,
                    'class' => $entity->entityType,
                    'policy' => $this->policyName($entity),
                    'icon' => $entity->entityType === null ? null : ($icons[$entity->entityType] ?? null),
                    'action' => $tab->isGrid() ? null : array_key_first($cells),
                    'cells' => $cells,
                ];
            }

            $sections[$tab->value] = [
                'label' => __('filament-bouncer::roles.tabs.'.$tab->value),
                'grid' => $tab->isGrid(),
                'rows' => $rows,
            ];
        }

        return $sections;
    }

    /**
     * The columns, in the order they are read: the one granting the whole entity first,
     * then every action under the scope it belongs to.
     *
     * Only from the entities the grid draws: without that filter the single action of a page
     * would open a column every row answers with a dash.
     *
     * @return array{manage: array{action: string, label: string}, groups: list<array{scope: string, label: string, actions: list<array{action: string, label: string}>}>}
     */
    public function getColumnGroups(): array
    {
        $labels = app(Labels::class);
        $offered = $this->griddedActions();
        $groups = [];

        foreach ($this->catalog->actions as $action => $scope) {
            if (! isset($offered[$action])) {
                continue;
            }

            $groups[$scope->value] ??= ['scope' => $scope->value, 'label' => $labels->scope($scope), 'actions' => []];
            $groups[$scope->value]['actions'][] = ['action' => $action, 'label' => $labels->action($action)];
        }

        return [
            'manage' => [
                'action' => Ability::MANAGE_ACTION,
                'label' => __('filament-bouncer::roles.form.manage'),
            ],
            'groups' => array_values($groups),
        ];
    }

    /**
     * Which entities a shortcut pressed from the corner of the table reaches.
     *
     * The gridded ones and no others: a page or a widget does not declare the action the
     * shortcut names, so applying it there would say nothing and wipe what they do say.
     *
     * @return list<string>
     */
    public function getGriddedEntities(): array
    {
        $keys = [];

        foreach ($this->getSections() as $section) {
            if ($section['grid']) {
                $keys = [...$keys, ...array_column($section['rows'], 'key')];
            }
        }

        return $keys;
    }

    public function getEntityLabel(): string
    {
        return __('filament-bouncer::roles.grid.entity');
    }

    public function getClearLabel(): string
    {
        return __('filament-bouncer::roles.grid.clear');
    }

    /**
     * What the corner mark on a cell means, said once instead of on every cell.
     *
     * The view only draws it when there is one. A legend about something not on the screen
     * teaches people not to read legends.
     */
    public function getNoteLegend(): string
    {
        return __('filament-bouncer::roles.grid.note_legend');
    }

    /**
     * The shortcut an entity's row offers.
     *
     * Only reading: "everything" and "nothing" need no list, and a shortcut for withdrawing or
     * for the irreversible is one nobody should have.
     *
     * Exclusive — it grants what it names and silences the rest of the row. Adding without taking
     * away would make "read only" mean "reading on top of whatever was there".
     *
     * @return list<array{key: string, label: string, actions: list<string>}>
     */
    public function getPresets(): array
    {
        $offered = $this->griddedActions();
        $read = [];

        foreach ($this->catalog->actions as $action => $scope) {
            if ($scope === AbilityScope::Read && isset($offered[$action])) {
                $read[] = $action;
            }
        }

        return $read === [] ? [] : [[
            'key' => 'read',
            'label' => __('filament-bouncer::roles.grid.preset_read'),
            'actions' => $read,
        ]];
    }

    /**
     * @return array<string, string>
     */
    public function getStances(): array
    {
        return app(Labels::class)->stances();
    }

    public function getNeutral(): string
    {
        return Stance::Neutral->value;
    }

    public function getEmptyLabel(): string
    {
        return __('filament-bouncer::roles.form.empty');
    }

    public function isEmptyCatalog(): bool
    {
        return $this->catalog->isEmpty();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getNotes(): array
    {
        $record = $this->recordOrNull();

        if (! $record instanceof Model || ! $this->notes instanceof Closure) {
            return [];
        }

        /** @var array<string, array<string, string>> $notes */
        $notes = ($this->notes)($record);

        return $notes;
    }

    /**
     * The rows the role answers yes to without holding a rule that names the ability.
     *
     * A role holding nothing but the wildcard has no row of its own for any of these, so a grid
     * reading only its own rows would say it can do nothing at all.
     *
     * @return array<string, array<string, bool>>
     */
    public function getBroader(): array
    {
        $record = $this->recordOrNull();

        if (! $record instanceof Model) {
            return [];
        }

        $abilities = app(RoleAbilities::class);
        $state = $abilities->toFormState($record);
        $broader = [];

        foreach ($this->catalog->entities as $key => $entity) {
            foreach ($entity->cells() as $action => $ability) {
                $broader[$key][$action] = ($state[$key][$action] ?? '') === Stance::Neutral->value
                    && $abilities->holds($record, $ability);
            }
        }

        return $broader;
    }

    /**
     * Which actions the grid holds a column for.
     *
     * @return array<string, true>
     */
    private function griddedActions(): array
    {
        $offered = [];

        foreach ($this->catalog->entities as $entity) {
            if ($entity->kind->tab()->isGrid()) {
                $offered += array_fill_keys(array_keys($entity->cells()), true);
            }
        }

        unset($offered[Ability::MANAGE_ACTION]);

        return $offered;
    }

    /**
     * The policy an entity's columns come from, said under its name.
     *
     * It is what makes the row decidable: its columns are the methods of that class and of
     * no other, so anybody wondering why a column is missing knows which file to open.
     */
    private function policyName(Entity $entity): ?string
    {
        if ($entity->entityType === null) {
            return null;
        }

        $policy = Gate::getPolicyFor($entity->entityType);

        return is_object($policy) ? class_basename($policy) : null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function neutralState(Catalog $catalog): array
    {
        $state = [];

        foreach ($catalog->entities as $key => $entity) {
            foreach (array_keys($entity->cells()) as $action) {
                $state[$key][$action] = Stance::Neutral->value;
            }
        }

        return $state;
    }

    /**
     * The record, when there is a form around this field to ask.
     *
     * A creation screen has no record and answers null on its own, but a field asked what
     * it holds before it has been put in a schema throws instead: the container is a typed
     * property and nothing has assigned it yet. Both mean the same thing here — there is
     * no role to read rules off — and the catalogue can still describe itself either way.
     */
    private function recordOrNull(): ?Model
    {
        $record = isset($this->container) ? $this->getRecord() : null;

        return $record instanceof Model ? $record : null;
    }
}
