<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
            Section::make('Role')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),
                    TextInput::make('title')
                        ->maxLength(150),
                ])
                ->columns(2),
            Section::make('Abilities')
                ->description('Only the abilities you hold yourself are shown, because those are the only ones you are able to hand on — or to take away.')
                ->schema(self::matrix(app(EditableCatalog::class)->current())),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function matrix(Catalog $catalog): array
    {
        if ($catalog->isEmpty()) {
            return [Text::make('You hold no abilities of your own, so there is nothing here to hand on.')];
        }

        $columns = count($catalog->actions) + 1;

        $rows = [
            Grid::make($columns)->schema(self::scopeHeadings($catalog)),
            Grid::make($columns)->schema(self::actionHeadings($catalog)),
        ];

        foreach ($catalog->subjects as $subject) {
            $rows[] = Grid::make($columns)->schema(self::cells($subject, $catalog));
        }

        return $rows;
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

            $cells[] = Text::make(Str::headline($value))
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
            $cells[] = Text::make(Str::headline($action));
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
                    ->options(Stance::labels())
                    ->colors(Stance::colors())
                    ->default(Stance::Neutral->value)
                    ->required()
                    ->grouped()
                : Text::make('');
        }

        return $cells;
    }
}
