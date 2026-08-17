<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Database\Models;

/**
 * Whether an authority already holds a role by name, read straight off the pivot.
 *
 * Bouncer's `assign()` is idempotent — reassigning a role somebody already has writes no row —
 * but nothing stops a caller from announcing an assignment anyway. Asked of the pivot and not
 * of the clipboard, which may be stale at the very moment a write is about to happen: asking it
 * instead would risk answering "no" right after a grant nobody has refreshed yet.
 */
final class RoleHolding
{
    public static function of(Model $authority, string $role): bool
    {
        $row = Models::role()->newQuery()->where('name', $role)->first();

        if (! $row instanceof Model) {
            return false;
        }

        return DB::table(Models::table('assigned_roles'))
            ->where('role_id', $row->getKey())
            ->where('entity_id', $authority->getKey())
            ->where('entity_type', $authority->getMorphClass())
            ->exists();
    }
}
