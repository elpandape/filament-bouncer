<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogTab;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The whole grid as one field, drawn as a table.
 *
 * Filament's own components cannot express this screen. A cell has to be a single
 * control that walks three stances, and `ToggleButtons` always draws one button per
 * option; a row has to keep its name visible while the columns scroll, and a schema
 * grid is not a table. So the package ships a view of its own, and this is the field
 * that feeds it.
 *
 * The entire nested state lives here, under one path, rather than a field per cell.
 * Cells are written by Alpine against that array, which is what lets a cell be a single
 * button and lets the header and the first column stay put while the rest scrolls.
 */
final class AbilityGrid extends Field
{
    protected string $view = 'filament-bouncer::forms.ability-grid';

    private Catalog $catalog;

    private ?Closure $notes = null;

    public function catalog(Catalog $catalog): static
    {
        $this->catalog = $catalog;

        return $this;
    }

    /**
     * What each cell says beyond its stance, worked out from the record being edited.
     */
    public function notes(Closure $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return array<string, array{label: string, grid: bool, subjects: array<string, array{label: string, policy: string|null, manage: string|null, actions: array<int, string>}>}>
     */
    public function getTabs(): array
    {
        $tabs = [];

        foreach ($this->catalog->tabs() as $value => $subjects) {
            $tab = CatalogTab::from((string) $value);

            $tabs[$tab->value] = [
                'label' => __('filament-bouncer::roles.tabs.'.$tab->value),
                'grid' => $tab->isGrid(),
                'subjects' => array_map($this->describe(...), $subjects),
            ];
        }

        return $tabs;
    }

    /**
     * The action columns, in the order the catalogue laid them out.
     *
     * Narrowed to what the gridded subjects actually declare. The catalogue's own list
     * is the union across every kind, and a page, a widget or an ability declared in
     * configuration each answer an action of their own — `view` and `use` — which no
     * model has. Taken whole, the grid grew a column no row in it could ever fill.
     *
     * Neither `getActions()` nor `getColumns()`: a schema component already has both,
     * and they mean something else entirely — the first is its buttons, the second its
     * responsive column count.
     *
     * @return array<string, array{label: string, scope: string}>
     */
    public function getActionColumns(): array
    {
        $declared = $this->declaredActions();
        $actions = [];

        foreach ($this->catalog->actions as $action => $scope) {
            if (! in_array($action, $declared, true)) {
                continue;
            }

            $actions[$action] = [
                'label' => app(Labels::class)->action($action),
                'scope' => $scope->value,
            ];
        }

        return $actions;
    }

    /**
     * The band above the columns. The actions arrive already grouped by scope, so each
     * heading simply spans as many columns as its scope has actions.
     *
     * @return array<int, array{scope: string, label: string, span: int}>
     */
    public function getBands(): array
    {
        $spans = [];

        foreach ($this->getActionColumns() as $column) {
            $spans[$column['scope']] = ($spans[$column['scope']] ?? 0) + 1;
        }

        $bands = [];

        foreach ($spans as $value => $span) {
            $scope = AbilityScope::from($value);

            $bands[] = [
                'scope' => $scope->value,
                'label' => app(Labels::class)->scope($scope),
                'span' => $span,
            ];
        }

        return $bands;
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

    /**
     * The order a cell walks when it is clicked.
     *
     * Not the order the cases are declared in: from saying nothing the next thing
     * somebody means is to grant, and forbidding is the step past that. Landing on a
     * denial by pressing once would be a trap.
     *
     * @return array<int, string>
     */
    public function getOrder(): array
    {
        return [
            Stance::Neutral->value,
            Stance::Granted->value,
            Stance::Forbidden->value,
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getNotes(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Model || ! $this->notes instanceof Closure) {
            return [];
        }

        /** @var array<string, array<string, string>> $notes */
        $notes = ($this->notes)($record);

        return $notes;
    }

    public function getSubjectHeading(): string
    {
        return __('filament-bouncer::roles.form.subject');
    }

    public function getManageHeading(): string
    {
        return __('filament-bouncer::roles.form.manage');
    }

    public function getUndeclaredLabel(): string
    {
        return __('filament-bouncer::roles.form.undeclared');
    }

    public function getEmptyLabel(): string
    {
        return __('filament-bouncer::roles.form.empty');
    }

    /**
     * Every action the subjects laid out as a grid answer between them.
     *
     * @return array<int, string>
     */
    private function declaredActions(): array
    {
        $declared = [];

        foreach ($this->catalog->subjects as $subject) {
            if (! $subject->kind->tab()->isGrid()) {
                continue;
            }

            foreach (array_keys($subject->abilities) as $action) {
                $declared[$action] = true;
            }
        }

        return array_keys($declared);
    }

    /**
     * @return array{label: string, policy: string|null, manage: string|null, actions: array<int, string>}
     */
    private function describe(Subject $subject): array
    {
        $policy = $subject->entityType === null ? null : Gate::getPolicyFor($subject->entityType);

        return [
            'label' => $subject->label,
            'policy' => is_object($policy) ? class_basename($policy) : null,
            'manage' => $subject->manage instanceof Ability ? Ability::MANAGE_ACTION : null,
            'actions' => array_keys($subject->abilities),
        ];
    }
}
