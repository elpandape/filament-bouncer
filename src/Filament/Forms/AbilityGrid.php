<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogTab;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The whole catalogue as one field, laid out as sections of rows.
 *
 * Filament's own components cannot express this screen. Three stances have to be offered
 * side by side and written without a round trip, and `ToggleButtons` would need a field
 * per cell: a catalogue of thirty resources would mount a hundred and eighty Livewire
 * components and spend a request on every click. So the package ships a view of its own,
 * and this is the field that feeds it.
 *
 * The entire nested state lives here under one path — subject, action, stance — rather
 * than a field per cell. That path is not a decision of this screen's: it is the shape
 * the store reads and writes, so a rewrite of the view cannot break the save.
 *
 * A subject is a row and an action is a column. That is what makes the screen answerable:
 * the question somebody comes here with is whether this role may delete, and reading it
 * down a column across every subject is the only layout that answers it without scrolling
 * back and forth. The price is that a cell has room for one control instead of three, so
 * the stance became a box that cycles rather than three buttons side by side.
 *
 * The columns are the union of the actions any policy declares, so they grow on their own.
 * The subject column is therefore pinned and the table scrolls: a row whose subject has
 * left the screen cannot be read.
 */
final class AbilityGrid extends Field
{
    protected string $view = 'filament-bouncer::forms.ability-matrix';

    private Catalog $catalog;

    private ?Closure $notes = null;

    private bool $submitsFromSummary = false;

    private ?string $summaryCancelUrl = null;

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
     * Put the save and the way out inside the summary bar, instead of a button row of
     * the page's own below it.
     *
     * The page that wants this asks for it; the creation wizard never does, because
     * there the save belongs to the wizard's last step. The buttons also stay away from
     * a disabled grid — a record page reads, it does not save.
     */
    public function submitsFromSummary(string $cancelUrl): static
    {
        $this->submitsFromSummary = true;
        $this->summaryCancelUrl = $cancelUrl;

        return $this;
    }

    public function doesSubmitFromSummary(): bool
    {
        return $this->submitsFromSummary && ! $this->isDisabled();
    }

    public function getSummaryCancelUrl(): ?string
    {
        return $this->summaryCancelUrl;
    }

    /**
     * The catalogue, ready to draw: a section per tab, a row per subject.
     *
     * Only the first tab is a grid. A page, a widget or an ability declared in
     * configuration answers exactly one action, and a grid one column wide reads worse
     * than a list does — so those sections carry the action on the row and are drawn as
     * lines.
     *
     * @return array<string, array{label: string, grid: bool, rows: list<array{key: string, label: string, class: string|null, policy: string|null, icon: string|null, action: string|null, cells: array<string, bool>}>}>
     */
    public function getSections(): array
    {
        /** @var array<string, string> $icons */
        $icons = config('filament-bouncer.icons', []);

        $sections = [];

        foreach ($this->catalog->tabs() as $value => $subjects) {
            $tab = CatalogTab::from((string) $value);
            $rows = [];

            foreach ($subjects as $key => $subject) {
                $cells = array_fill_keys(array_keys($subject->cells()), true);

                $rows[] = [
                    'key' => $key,
                    'label' => $subject->label,
                    'class' => $subject->entityType,
                    'policy' => $this->policyName($subject),
                    'icon' => $subject->entityType === null ? null : ($icons[$subject->entityType] ?? null),
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
     * The columns, in the order they are read: the one granting the whole subject first,
     * then every action under the scope it belongs to.
     *
     * They come only from the subjects the grid draws. Without that filter the single
     * action of a page would open a column every row of the matrix answers with a dash.
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
     * Which subjects a shortcut pressed from the corner of the table reaches.
     *
     * The gridded ones and no others: a page or a widget does not declare the action the
     * shortcut names, so applying it there would say nothing and wipe what they do say.
     *
     * @return list<string>
     */
    public function getGriddedSubjects(): array
    {
        $keys = [];

        foreach ($this->getSections() as $section) {
            if ($section['grid']) {
                $keys = [...$keys, ...array_column($section['rows'], 'key')];
            }
        }

        return $keys;
    }

    public function getSubjectLabel(): string
    {
        return __('filament-bouncer::roles.grid.subject');
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
     * The shortcut a subject's row offers.
     *
     * Only reading. "Everything" and "nothing" need no list, because they answer for
     * whatever the subject happens to declare, and a shortcut for withdrawing or for the
     * irreversible is a shortcut nobody should have.
     *
     * It is exclusive: it grants what it names and silences the rest of that row. Adding
     * without taking away would make "read only" mean "reading on top of whatever was
     * already there", which is the opposite of what its name promises.
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
     * A role holding nothing but the wildcard has no row of its own for any of these, so
     * a grid reading only its own rows would draw the dash everywhere and say the role
     * can do nothing at all.
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

        foreach ($this->catalog->subjects as $key => $subject) {
            foreach ($subject->cells() as $action => $ability) {
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

        foreach ($this->catalog->subjects as $subject) {
            if ($subject->kind->tab()->isGrid()) {
                $offered += array_fill_keys(array_keys($subject->cells()), true);
            }
        }

        unset($offered[Ability::MANAGE_ACTION]);

        return $offered;
    }

    /**
     * The policy a subject's columns come from, said under its name.
     *
     * It is what makes the row decidable: its columns are the methods of that class and of
     * no other, so anybody wondering why a column is missing knows which file to open.
     */
    private function policyName(Subject $subject): ?string
    {
        if ($subject->entityType === null) {
            return null;
        }

        $policy = Gate::getPolicyFor($subject->entityType);

        return is_object($policy) ? class_basename($policy) : null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function neutralState(Catalog $catalog): array
    {
        $state = [];

        foreach ($catalog->subjects as $key => $subject) {
            foreach (array_keys($subject->cells()) as $action) {
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
