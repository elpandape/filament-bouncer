<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles;

use BackedEnum;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ListRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables\RolesTable;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;
use UnitEnum;

final class RoleResource extends Resource
{
    public static function getModel(): string
    {
        /** @var class-string<Role> $model */
        $model = Models::classname(Role::class);

        return $model;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        /** @var string $slug */
        $slug = config('filament-bouncer.navigation.slug', 'security/roles');

        return $slug;
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        /** @var string|BackedEnum|Htmlable|null $icon */
        $icon = config('filament-bouncer.navigation.icon');

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
        $sort = config('filament-bouncer.navigation.sort');

        return $sort;
    }

    public static function getRecordTitleAttribute(): string
    {
        return 'name';
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return self::isOffLimits($record) === false && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return self::isOffLimits($record) === false && parent::canDelete($record);
    }

    /**
     * Two roles nobody edits from this screen, whatever their policy says.
     *
     * The first is the role holding everything: it is the way back in when a mistake
     * leaves nobody able to hand out abilities, and a way back in that can be edited is
     * not one. The second is any role the person at the keyboard holds themselves, so
     * that raising your own reach is never one save away.
     *
     * This is checked here rather than only on the button, because the edit page asks
     * the same question when it mounts. A request built by hand meets it too.
     */
    private static function isOffLimits(Model $record): bool
    {
        /** @var string $name */
        $name = $record->getAttribute('name');

        if (app(PrivilegedRole::class)->isNamed($name)) {
            return true;
        }

        $authority = Filament::auth()->user();

        return $authority instanceof Model && app(Bouncer::class)->is($authority)->an($name);
    }
}
