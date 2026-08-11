<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles;

use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ListRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables\RolesTable;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

/**
 * The screen roles are composed on.
 *
 * Two refusals live here rather than on the table, and they are the reason this class is
 * worth reading before it is changed. Filament asks the resource whether a record may be
 * edited or deleted from three places at once — the row's buttons, the page it opens and
 * the request that page receives — so an answer given here is given to all three, and an
 * armed request meets the same wall a hidden button does.
 *
 * Hiding the buttons alone would be theatre: the edit page is a URL, and a role is a name
 * away from granting itself everything.
 */
final class RoleResource extends Resource
{
    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        /** @var class-string<Model> $model */
        $model = Models::classname(Role::class);

        return $model;
    }

    public static function getRecordTitleAttribute(): string
    {
        return 'name';
    }

    public static function getModelLabel(): string
    {
        return __('filament-bouncer::roles.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-bouncer::roles.resource.plural');
    }

    /**
     * Where the screen lives, and how it is announced.
     *
     * All four come out of configuration because none of them is this package's decision:
     * the group has whatever name that panel calls its own, the order depends on what
     * else is in it, and the icon on whichever family the project already draws with.
     */
    public static function getSlug(?Panel $panel = null): string
    {
        /** @var string $slug */
        $slug = config('filament-bouncer.navigation.slug', 'security/roles');

        return $slug;
    }

    public static function getNavigationIcon(): ?string
    {
        /** @var string|null $icon */
        $icon = config('filament-bouncer.navigation.icon');

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
        $sort = config('filament-bouncer.navigation.sort');

        return $sort;
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
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
        return self::mayBeChanged($record) && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return self::mayBeChanged($record) && parent::canDelete($record);
    }

    /**
     * The two roles nobody works on from here, whatever their abilities say.
     *
     * Your own, because everything you may hand out you may hand to yourself, and a role
     * you hold is the shortest way there: one save and the account at the keyboard has
     * whatever it just wrote. Somebody else's account is another matter — that is the
     * screen doing its job.
     *
     * And the privileged one, because it is the way back in when a mistake leaves nobody
     * able to hand anything out. A way back in that can be edited is not one.
     */
    private static function mayBeChanged(Model $record): bool
    {
        /** @var string $name */
        $name = $record->getAttribute('name');

        if (app(PrivilegedRole::class)->isNamed($name)) {
            return false;
        }

        $editor = Filament::auth()->user();

        return ! ($editor instanceof Model
            && method_exists($editor, 'isAn')
            && $editor->isAn($name) === true);
    }
}
