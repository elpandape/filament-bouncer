<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogTab;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\Restriction;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * The roles form: a name, and a grid of subjects against actions.
 *
 * The grid only ever offers the catalogue narrowed to what the person filling it in
 * holds themselves, which is half of the rule that nobody hands out what they do not
 * have. The other half is applied again when the form is saved.
 */
final class RoleForm
{
    /**
     * Where the grid keeps its state. It is not a column on the role, so both pages
     * that write take it back out of the data before the record is touched.
     */
    public const string ABILITIES = 'abilities';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-bouncer::roles.form.role'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('filament-bouncer::roles.form.name'))
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),
                    TextInput::make('title')
                        ->label(__('filament-bouncer::roles.form.title'))
                        ->maxLength(150),
                ])
                ->columns(2),
            Section::make(__('filament-bouncer::roles.form.abilities'))
                ->description(__('filament-bouncer::roles.form.description'))
                // The whole width, always. A resource form lays its sections out in two
                // columns by default, and half a page divided by as many actions as the
                // catalogue holds leaves each cell too narrow to read.
                ->columnSpanFull()
                ->schema(self::matrix(app(EditableCatalog::class)->current())),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function matrix(Catalog $catalog): array
    {
        if ($catalog->isEmpty()) {
            return [Text::make(__('filament-bouncer::roles.form.empty'))];
        }

        $groups = $catalog->tabs();

        // One tab is not a tab, it is a heading nobody asked for. A panel that only
        // exposes resources reads exactly as it did before this existed.
        if (count($groups) === 1) {
            return self::group(CatalogTab::from(array_key_first($groups)), reset($groups), $catalog);
        }

        $tabs = [];

        foreach ($groups as $value => $subjects) {
            $tab = CatalogTab::from((string) $value);

            $tabs[] = Tab::make(__('filament-bouncer::roles.tabs.'.$tab->value))
                ->badge(count($subjects))
                ->schema(self::group($tab, $subjects, $catalog));
        }

        return [Tabs::make()->tabs($tabs)];
    }

    /**
     * One tab's worth of the catalogue.
     *
     * @param  array<string, Subject>  $subjects
     * @return array<int, Component>
     */
    private static function group(CatalogTab $tab, array $subjects, Catalog $catalog): array
    {
        if (! $tab->isGrid()) {
            return array_values(array_map(self::row(...), $subjects));
        }

        $columns = count($catalog->actions) + 1;

        $rows = [
            Grid::make($columns)->schema(self::scopeHeadings($catalog)),
            Grid::make($columns)->schema(self::actionHeadings($catalog)),
        ];

        foreach ($subjects as $subject) {
            $rows[] = Grid::make($columns)->schema(self::cells($subject, $catalog));
        }

        return $rows;
    }

    /**
     * What the cell cannot say by itself.
     *
     * The buttons hold the row that names this ability exactly, because that is the
     * row they write back. Bouncer answers on more than that: a role granted
     * everything holds no row for any of these and still answers yes to all of them,
     * and a broader denial beats a grant made right here. Left unsaid, the grid would
     * report "not granted" about an ability the role plainly has — the one lie a
     * screen like this must not tell.
     */
    private static function inheritance(Ability $ability): Closure
    {
        return static function (mixed $record, mixed $state) use ($ability): ?string {
            if (! $record instanceof Model) {
                return null;
            }

            $abilities = app(RoleAbilities::class);
            $direct = Stance::tryFrom(is_string($state) ? $state : '') ?? Stance::Neutral;
            $holds = $abilities->holds($record, $ability);

            $said = [];

            if ($direct === Stance::Neutral && $holds) {
                $said[] = __('filament-bouncer::roles.form.inherited');
            }

            if ($direct === Stance::Granted && ! $holds) {
                $said[] = __('filament-bouncer::roles.form.overruled');
            }

            $restriction = $abilities->restrictions($record)[$ability->identity()] ?? new Restriction;

            if ($restriction->owned) {
                $said[] = __('filament-bouncer::roles.form.restricted_owned');
            }

            if ($restriction->records > 0) {
                $said[] = trans_choice('filament-bouncer::roles.form.restricted_records', $restriction->records, [
                    'count' => $restriction->records,
                ]);
            }

            return $said === [] ? null : implode(' ', $said);
        };
    }

    /**
     * A subject with a single ability: a door, not a grid.
     *
     * The buttons are joined and the label sits beside them, because here there is a
     * whole row to spend and nothing to line the cell up against.
     */
    private static function row(Subject $subject): ToggleButtons
    {
        // Asked for by key rather than with reset(), which takes its array by reference
        // and so cannot be pointed at a readonly property at all.
        $action = (string) array_key_first($subject->abilities);
        $ability = $subject->abilities[$action];

        return ToggleButtons::make(self::ABILITIES.'.'.$subject->key.'.'.$action)
            ->label($subject->label)
            ->helperText(self::inheritance($ability))
            ->hint($ability->title)
            ->inlineLabel()
            ->grouped()
            ->options(app(Labels::class)->stances())
            ->colors(Stance::colors())
            ->default(Stance::Neutral->value)
            ->required();
    }

    /**
     * The band above the columns, tinted so that the weight of a group can be seen
     * before it is read. The actions arrive already grouped by scope, so each heading
     * simply spans as many columns as its scope has actions.
     *
     * @return array<int, Component>
     */
    private static function scopeHeadings(Catalog $catalog): array
    {
        $spans = [];

        foreach ($catalog->actions as $scope) {
            $spans[$scope->value] = ($spans[$scope->value] ?? 0) + 1;
        }

        $cells = [Text::make('')];

        foreach ($spans as $value => $span) {
            $scope = AbilityScope::from($value);

            $cells[] = Text::make(app(Labels::class)->scope($scope))
                ->color($scope->color())
                ->columnSpan($span);
        }

        return $cells;
    }

    /**
     * @return array<int, Component>
     */
    private static function actionHeadings(Catalog $catalog): array
    {
        $cells = [Text::make('')];

        foreach (array_keys($catalog->actions) as $action) {
            $cells[] = Text::make(app(Labels::class)->action($action));
        }

        return $cells;
    }

    /**
     * @return array<int, Component>
     */
    private static function cells(Subject $subject, Catalog $catalog): array
    {
        $cells = [Text::make($subject->label)];

        foreach (array_keys($catalog->actions) as $action) {
            $ability = $subject->ability($action);

            $cells[] = $ability instanceof Ability
                ? ToggleButtons::make(self::ABILITIES.'.'.$subject->key.'.'.$action)
                    ->label($ability->title)
                    ->hiddenLabel()
                    ->helperText(self::inheritance($ability))
                    ->options(app(Labels::class)->stances())
                    ->colors(Stance::colors())
                    ->icons(Stance::icons())
                    // Marks, not words. The word survives as the button's accessible name
                    // and as its tooltip, so nothing is lost by not printing it in a cell
                    // too narrow to hold it.
                    ->hiddenButtonLabels()
                    ->tooltips(app(Labels::class)->stances())
                    ->inline()
                    ->grouped()
                    ->default(Stance::Neutral->value)
                    ->required()
                : Text::make('');
        }

        return $cells;
    }
}
