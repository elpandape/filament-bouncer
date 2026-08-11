<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ViewAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables\AbilitiesTable;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

/**
 * The other end of the roles screen: not what a role may do, but what may be done.
 *
 * One refusal lives here, and it is a refusal to offer something rather than to allow it.
 * Filament asks the resource whether a record may be deleted from the row, the record
 * page and the request that page receives, so answering once here answers all three — and
 * the answer is no, always, whoever is asking and whatever they hold.
 *
 * That is not caution. A row of this table is pointed at by every grant that was ever
 * made from it, and deleting the row takes all of them with it silently. The way a row is
 * meant to go is that the code stops declaring it and `filament-bouncer:reconcile --prune`
 * sweeps it, saying how many it swept. Leaving the button off is what keeps the two from
 * being one click apart.
 *
 * Saying so out loud matters more than it looks: Filament falls open on a policy method
 * that is not there, and `AbilityRowPolicy` deliberately declares no `delete`. Left to the
 * policy alone, the button would be offered to everybody.
 */
final class AbilityResource extends Resource
{
    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        /** @var class-string<Model> $model */
        $model = Models::classname(Ability::class);

        return $model;
    }

    public static function getRecordTitleAttribute(): string
    {
        return 'name';
    }

    public static function getModelLabel(): string
    {
        return __('filament-bouncer::abilities.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-bouncer::abilities.resource.plural');
    }

    /**
     * Where the screen lives, and how it is announced.
     *
     * The group is the roles screen's, because the two are the same thing read from its
     * two ends and nothing is served by filing them apart. Everything else is this
     * screen's own key, so it can be ordered and drawn beside its sibling without
     * inheriting its slug.
     */
    public static function getSlug(?Panel $panel = null): string
    {
        /** @var string $slug */
        $slug = config('filament-bouncer.abilities.slug', 'security/abilities');

        return $slug;
    }

    public static function getNavigationIcon(): ?string
    {
        /** @var string|null $icon */
        $icon = config('filament-bouncer.abilities.icon');

        return $icon;
    }

    public static function getNavigationGroup(): ?string
    {
        /** @var string|null $group */
        $group = config('filament-bouncer.navigation.group');

        return $group;
    }

    public static function getNavigationSort(): ?int
    {
        /** @var int|null $sort */
        $sort = config('filament-bouncer.abilities.sort');

        return $sort;
    }

    /**
     * One schema for reading and for changing, so that whoever learns where a thing is
     * on one screen finds it in the same place on the other.
     */
    public static function form(Schema $schema): Schema
    {
        return AbilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbilitiesTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListAbilities::route('/'),
            'create' => CreateAbility::route('/create'),
            'view' => ViewAbility::route('/{record}'),
            'edit' => EditAbility::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
