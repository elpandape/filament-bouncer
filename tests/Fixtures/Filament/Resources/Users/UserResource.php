<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users;

use ElPandaPe\FilamentBouncer\Filament\RelationManagers\RolesRelationManager;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\Users\Pages\ViewUser;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use Filament\Resources\Resource;

/**
 * The account screen the roles tab hangs off, which is what the relation manager needs to
 * be mounted against.
 */
final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            RolesRelationManager::class,
        ];
    }

    /** @return array<string, \Filament\Resources\Pages\PageRegistration> */
    public static function getPages(): array
    {
        return [
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
