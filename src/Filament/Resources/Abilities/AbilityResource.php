<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities;

use BackedEnum;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ViewAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityInfolist;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables\AbilitiesTable;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;
use UnitEnum;

/**
 * The other end of the roles screen: not what a role may do, but what may be done.
 *
 * Deleting is refused here, always and for everybody: a row is pointed at by every grant ever made
 * from it, and deleting it takes all of them silently. A row goes when the code stops declaring it
 * and `--prune` sweeps it, saying how many it swept.
 *
 * Saying so out loud matters: Filament falls open on a policy method that is not there, and
 * `AbilityPolicy` deliberately declares no `delete`.
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

    /**
     * The icon, whatever shape the application named it in.
     *
     * As wide as Filament's own signature rather than narrowed to a string: an icon shipped as a
     * backed enum would otherwise meet a type error from inside the sidebar, with the panel down.
     */
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        /** @var string|BackedEnum|Htmlable|null $icon */
        $icon = config('filament-bouncer.abilities.icon');

        return $icon;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        /** @var string|UnitEnum|null $group */
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
     * Reading and changing keep the same sections in the same order, so that whoever learns where a
     * thing is on one screen finds it in the same place on the other.
     */
    public static function form(Schema $schema): Schema
    {
        return AbilityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AbilityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbilitiesTable::configure($table);
    }

    /**
     * Reads past the tenant scope only where the installation does not use it.
     *
     * There a scoped row is an anomaly nothing else can see, so this screen is the one place it
     * can be mended; under tenancy the same read would put another tenant's rows on screen.
     *
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return app(Tenancy::class)->inUse() ? $query : $query->withoutGlobalScopes();
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

    public static function canEdit(Model $record): bool
    {
        return ! self::isWildcard($record) && parent::canEdit($record);
    }

    /**
     * Whether the row shows the padlock instead of a way of working on it.
     *
     * Deliberately not the policy's answer, the same as on the roles screen: the padlock
     * explains why a row that could otherwise be worked on is out of reach, and a reader the
     * policy refuses everything to would see it lie on every row.
     */
    public static function isLocked(Model $record): bool
    {
        return self::isWildcard($record);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * The one row nobody works on from here: the wildcard over the wildcard.
     *
     * `PrivilegedRole` asks the clipboard for exactly this pair to know whether the role that
     * holds everything still holds it, and writes this row back to restore it: renaming it takes
     * that role's reach away without the role being touched.
     */
    private static function isWildcard(Model $record): bool
    {
        return $record->getAttribute('name') === AbilityStore::WILDCARD
            && $record->getAttribute('entity_type') === AbilityStore::WILDCARD;
    }
}
