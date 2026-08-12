<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Silber\Bouncer\Database\Models;

/**
 * Every rule the store holds, gathered under the thing it decides about.
 *
 * A flat list of ability names is unreadable past about thirty rows, because the name is
 * the least distinguishing part: a dozen models all have a `view`. Grouping by model puts
 * the answer to "what may be decided about a post" in one place, which is the question
 * somebody arriving at this screen actually has. The grouping is fixed — the picker that
 * offered to regroup by anything else is hidden, because there is nothing else worth
 * grouping by — and the group's own heading carries the model: its label, how many rules
 * it holds, and the class in monospace, so no column has to repeat it row after row.
 *
 * Two columns carry what no name can say. The reconciliation's answer, in three states
 * rather than two, because a row nobody declares is on its way out and a row that was
 * never the reconciliation's business is in no danger at all — and a warning shown over
 * both is a warning nobody reads. And the holders, with what each of them says — the
 * grant in green and the denial in red, read off the pivot's own `forbidden` column,
 * because a denial and a grant are the same row seen from two roles and a grey chip
 * would flatten the one difference that matters.
 *
 * There is no delete, here or anywhere on this screen. A row goes when the code stops
 * declaring it and `--prune` takes it, which reports how many it swept; a button would
 * take every grant pointing at the row along with it, in one click and without a second
 * question.
 */
final class AbilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-bouncer::abilities.table.name'))
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->description(self::title(...)),
                TextColumn::make('reach')
                    ->label(__('filament-bouncer::abilities.table.reach'))
                    ->badge()
                    ->state(static fn (Model $record): string => Reach::reading($record))
                    ->color(static fn (Model $record): string => Reach::of($record) === Reach::All ? 'gray' : 'info'),
                TextColumn::make('declared')
                    ->label(__('filament-bouncer::abilities.declared.label'))
                    ->badge()
                    ->state(static fn (Model $record): string => Declaration::of($record)->label())
                    ->color(static fn (Model $record): string => Declaration::of($record)->color()),
                TextColumn::make('holders')
                    ->label(__('filament-bouncer::abilities.table.holders'))
                    ->badge()
                    ->state(self::holders(...))
                    ->color(self::holderColor(...)),
            ])
            ->groups([
                Group::make('entity_type')
                    ->label(__('filament-bouncer::abilities.table.entity'))
                    ->getTitleFromRecordUsing(self::groupTitle(...))
                    ->getDescriptionFromRecordUsing(self::groupClass(...)),
            ])
            ->defaultGroup('entity_type')
            ->groupingSettingsHidden()
            ->recordActions([
                ViewAction::make()
                    ->link()
                    ->icon(null),
                EditAction::make()
                    ->link()
                    ->icon(null),
            ])
            ->defaultSort('name')
            ->emptyStateHeading(__('filament-bouncer::abilities.table.empty'));
    }

    private static function title(Model $record): ?string
    {
        /** @var string|null $title */
        $title = $record->getAttribute('title');

        return $title;
    }

    /**
     * What the rule decides about, in the words the panel uses for it.
     *
     * The store keeps the morph class, so an application with a morph map has names here
     * the catalogue never keyed anything under. Falling back to the stored value is right
     * rather than merely safe: it is what the row actually says.
     */
    private static function subject(Model $record): string
    {
        $type = $record->getAttribute('entity_type');

        if (! is_string($type)) {
            return __('filament-bouncer::abilities.form.no_entity');
        }

        $subject = app(CatalogRegistry::class)->current()->subject(Subject::keyFor($type));

        return $subject instanceof Subject ? $subject->label : $type;
    }

    /**
     * The group's heading: the model, and how many rules the group gathers.
     */
    private static function groupTitle(Model $record): string
    {
        $type = $record->getAttribute('entity_type');

        $query = Models::ability()->newQuery();

        $count = (is_string($type) ? $query->where('entity_type', $type) : $query->whereNull('entity_type'))->count();

        return self::subject($record).' — '.trans_choice('filament-bouncer::abilities.table.group_count', $count, ['count' => $count]);
    }

    /**
     * The class under the group's heading, in monospace, only where the heading itself
     * is not already the class: a model the catalogue never keyed puts its class in the
     * title, and repeating it below would say the same thing twice.
     */
    private static function groupClass(Model $record): ?HtmlString
    {
        $type = $record->getAttribute('entity_type');

        if (! is_string($type)) {
            return null;
        }

        if (! app(CatalogRegistry::class)->current()->subject(Subject::keyFor($type)) instanceof Subject) {
            return null;
        }

        return new HtmlString('<span class="fb-code">'.e($type).'</span>');
    }

    /**
     * Which roles say something about the rule, and which of the two things they say.
     *
     * Asked of the store one role at a time rather than joined by hand, so that this
     * column and the cell on the roles screen cannot come to disagree: they are the same
     * question of the same row, and only one of them is allowed to know how to answer it.
     *
     * @return array<int, string>
     */
    private static function holders(Model $record): array
    {
        $abilities = app(RoleAbilities::class);
        $labels = app(Labels::class);
        $holders = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            $stance = $abilities->stanceOnRow($role, $record);

            if ($stance === Stance::Neutral) {
                continue;
            }

            /** @var string $name */
            $name = $role->getAttribute('name');

            $holders[] = __('filament-bouncer::abilities.table.holder', [
                'role' => $name,
                'stance' => $labels->stance($stance),
            ]);
        }

        return $holders;
    }

    /**
     * The colour a holder's chip wears, told apart by the suffix the chip itself was
     * composed with two methods up — the pivot's `forbidden` in red, the grant in green.
     */
    private static function holderColor(string $state): string
    {
        return str_ends_with($state, app(Labels::class)->stance(Stance::Forbidden)) ? 'danger' : 'success';
    }
}
