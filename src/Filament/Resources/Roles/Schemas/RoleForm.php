<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Store\Restriction;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * The roles form: a name, and the grid.
 *
 * The grid offers the whole catalogue to whoever the policy let onto the screen. Being
 * trusted to edit roles is the whole of the trust: there is no second, smaller set of
 * abilities somebody may hold but not hand on.
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
        $catalog = app(CatalogRegistry::class)->current();

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
                ->schema([
                    AbilityGrid::make(self::ABILITIES)
                        ->hiddenLabel()
                        ->catalog($catalog)
                        ->notes(self::notes($catalog))
                        // A role being created holds nothing yet. Every cell carried this
                        // when every cell was a field of its own; the grid carries it now.
                        ->default(self::neutral($catalog)),
                ]),
        ]);
    }

    /**
     * The state a role that holds nothing arrives in.
     *
     * @return array<string, array<string, string>>
     */
    private static function neutral(Catalog $catalog): array
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
     * What each cell says beyond its own stance.
     *
     * The buttons hold the row that names the ability exactly, because that is the row
     * they write back. Bouncer answers on more than that, and keeps rules the grid
     * cannot write at all. Left unsaid, a cell would report "not granted" about an
     * ability the role plainly has — the one lie a screen like this must not tell.
     */
    private static function notes(Catalog $catalog): Closure
    {
        return static function (Model $record) use ($catalog): array {
            $abilities = app(RoleAbilities::class);
            $state = $abilities->toFormState($record);
            $restrictions = $abilities->restrictions($record);

            $notes = [];

            foreach ($catalog->subjects as $key => $subject) {
                foreach ($subject->cells() as $action => $ability) {
                    $direct = Stance::tryFrom((string) ($state[$key][$action] ?? '')) ?? Stance::Neutral;
                    $holds = $abilities->holds($record, $ability);

                    $said = [];

                    if ($direct === Stance::Neutral && $holds) {
                        $said[] = __('filament-bouncer::roles.form.inherited');
                    }

                    if ($direct === Stance::Granted && ! $holds) {
                        $said[] = __('filament-bouncer::roles.form.overruled');
                    }

                    $restriction = $restrictions[$ability->identity()] ?? new Restriction;

                    if ($restriction->owned) {
                        $said[] = __('filament-bouncer::roles.form.restricted_owned');
                    }

                    if ($restriction->records > 0) {
                        $said[] = trans_choice('filament-bouncer::roles.form.restricted_records', $restriction->records, [
                            'count' => $restriction->records,
                        ]);
                    }

                    if ($said !== []) {
                        $notes[$key][$action] = implode(' ', $said);
                    }
                }
            }

            return $notes;
        };
    }
}
