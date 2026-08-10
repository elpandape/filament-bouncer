<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities;

use BackedEnum;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ViewAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Tables\AbilitiesTable;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;
use UnitEnum;

/**
 * The other axis, as a resource: not what a role can do, but who can do a thing.
 *
 * It reads, it renames, and it narrows. What it does not do is invent: a plain ability is
 * a method on a policy, a page, a widget or a name in configuration, and
 * `filament-bouncer:reconcile` is what writes it — a row made from a form would be one
 * the catalogue does not declare, which `--check` fails on and `--prune` deletes. The
 * create screen therefore composes the one row the reconciliation never speaks for: an
 * ability narrowed to a single record, or to what its holder owns.
 */
final class AbilityResource extends Resource
{
    public static function getModel(): string
    {
        /** @var class-string<Ability> $model */
        $model = Models::classname(Ability::class);

        return $model;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        /** @var string $slug */
        $slug = config('filament-bouncer.abilities.slug', 'security/abilities');

        return $slug;
    }

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

    public static function getRecordTitleAttribute(): string
    {
        return 'title';
    }

    public static function form(Schema $schema): Schema
    {
        return AbilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbilitiesTable::configure($table);
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
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
}
