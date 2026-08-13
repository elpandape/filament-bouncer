<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists\Concerns;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Illuminate\Database\Eloquent\Model;

/**
 * The single reading of the store the entries drawing cells do.
 *
 * It lives here rather than in each of them because two readings of the same role are two
 * places a stance can come out different: one entry would say one thing and another the
 * opposite about the same row, and neither could contradict the other.
 */
trait ReadsStances
{
    /**
     * The word of each stance, which is what every mark carries in its title. Without them
     * the screen would be a grid of icons with nothing for a screen reader to read.
     *
     * @return array<string, string>
     */
    public function getStanceLabels(): array
    {
        return [
            ...app(Labels::class)->stances(),
            'broader' => __('filament-bouncer::roles.form.inherited'),
        ];
    }

    /**
     * The stance of every cell, already worked out.
     *
     * "Broader" is the fourth: it neither grants here nor truly abstains, but answers yes
     * through a wider rule. Without it a role holding the wildcard would read as one that
     * can do nothing at all.
     *
     * @return array<string, array<string, 'broader'|'forbidden'|'granted'|'neutral'>>
     */
    public function getStances(): array
    {
        $record = $this->roleOrNull();

        if (! $record instanceof Model) {
            return [];
        }

        $abilities = app(RoleAbilities::class);
        $state = $abilities->toFormState($record);
        $stances = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $key => $subject) {
            foreach ($subject->cells() as $action => $ability) {
                $stance = Stance::tryFrom($state[$key][$action] ?? '') ?? Stance::Neutral;

                $stances[$key][$action] = $stance === Stance::Neutral && $abilities->holds($record, $ability)
                    ? 'broader'
                    : $stance->value;
            }
        }

        return $stances;
    }

    /**
     * The subjects answering several actions, which are the only ones a table of subjects
     * against actions has room for.
     *
     * @return array<int, array{key: string, label: string, class: string|null, policy: string|null, icon: string|null, action: string|null, cells: array<string, bool>}>
     */
    public function gridRows(): array
    {
        $sections = array_filter($this->grid()->getSections(), static fn (array $section): bool => $section['grid']);

        return array_merge([], ...array_column($sections, 'rows'));
    }

    /**
     * The actions in the order they are read, so two rows never sort the same thing
     * differently.
     *
     * @return list<string>
     */
    protected function actionOrder(): array
    {
        $columns = $this->grid()->getColumnGroups();

        return [
            $columns['manage']['action'],
            ...array_column(array_merge([], ...array_column($columns['groups'], 'actions')), 'action'),
        ];
    }

    protected function grid(): AbilityGrid
    {
        return AbilityGrid::make(RoleForm::ABILITIES)->catalog(app(CatalogRegistry::class)->current());
    }
}
