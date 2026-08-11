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
 * A subject is a section and an action is a row, which is the other way round from a
 * matrix and is what buys the room. In a column, three buttons never fitted and the
 * stance had to be a shape the reader decoded; in a row they fit, and it is three words
 * to choose between.
 */
final class AbilityGrid extends Field
{
    protected string $view = 'filament-bouncer::forms.ability-grid';

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
     * What each row says beyond its stance, worked out from the record being edited.
     */
    public function notes(Closure $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * The catalogue, ready to draw: a section per tab, a subject per section, a row per
     * action.
     *
     * @return array<string, array{label: string, doors: bool, subjects: array<string, array{label: string, policy: string|null, rows: array<int, array{action: string, label: string, scope: string, note: string|null, broader: bool}>}>}>
     */
    public function getSections(): array
    {
        $labels = app(Labels::class);
        $notes = $this->getNotes();
        $broader = $this->getBroader();
        $sections = [];

        foreach ($this->catalog->tabs() as $value => $subjects) {
            $tab = CatalogTab::from((string) $value);
            $described = [];

            foreach ($subjects as $key => $subject) {
                $described[$key] = [
                    'label' => $subject->label,
                    'policy' => $this->policyFor($subject),
                    'rows' => $this->rowsFor($key, $subject, $labels, $notes, $broader),
                ];
            }

            $sections[$tab->value] = [
                'label' => __('filament-bouncer::roles.tabs.'.$tab->value),
                'doors' => ! $tab->isGrid(),
                'subjects' => $described,
            ];
        }

        return $sections;
    }

    /**
     * Which actions a preset would set.
     *
     * Only reading is offered as a shortcut of its own. "Everything" and "nothing" need
     * no list, because they answer for whatever the subject happens to declare, and a
     * shortcut for withdrawing or for the irreversible is a shortcut nobody should have.
     *
     * @return array<string, array<int, string>>
     */
    public function getPresets(): array
    {
        $read = [];

        foreach ($this->catalog->actions as $action => $scope) {
            if ($scope === AbilityScope::Read) {
                $read[] = $action;
            }
        }

        return ['read' => $read];
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

    public function getCollapseLabel(): string
    {
        return __('filament-bouncer::roles.form.collapse');
    }

    public function getScopeLabel(): string
    {
        return __('filament-bouncer::roles.form.scope');
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
     * @param  array<string, array<string, string>>  $notes
     * @param  array<string, array<string, bool>>  $broader
     * @return array<int, array{action: string, label: string, scope: string, note: string|null, broader: bool}>
     */
    private function rowsFor(string $key, Subject $subject, Labels $labels, array $notes, array $broader): array
    {
        $rows = [];

        foreach (array_keys($subject->cells()) as $action) {
            $rows[] = [
                'action' => $action,
                'label' => $action === Ability::MANAGE_ACTION
                    ? __('filament-bouncer::roles.form.manage')
                    : $labels->action($action),
                'scope' => ($this->catalog->actions[$action] ?? AbilityScope::Write)->value,
                'note' => $notes[$key][$action] ?? null,
                'broader' => $broader[$key][$action] ?? false,
            ];
        }

        return $rows;
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

    private function policyFor(Subject $subject): ?string
    {
        if ($subject->entityType === null) {
            return null;
        }

        $policy = Gate::getPolicyFor($subject->entityType);

        return is_object($policy) ? class_basename($policy) : null;
    }
}
