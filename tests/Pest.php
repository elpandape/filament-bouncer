<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade as Bouncer;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Creates a user, signs them in, and hands the model back.
 *
 * It returns the user rather than something chainable because almost every test that
 * signs somebody in then grants them something, and needs the model to do it.
 */
function signIn(): User
{
    $user = User::forceCreate([
        'name' => 'Amaru',
        'email' => Str::random(12).'@example.test',
        'password' => 'irrelevant',
    ]);

    Auth::login($user);

    return $user;
}

/**
 * Grants abilities and clears the cache, which no write to Bouncer does on its own.
 *
 * @param  array<int, array{0: string, 1: class-string|null}>  $abilities
 */
function grant(Illuminate\Database\Eloquent\Model $authority, array $abilities): void
{
    foreach ($abilities as [$name, $entity]) {
        Bouncer::allow($authority)->to($name, $entity);
    }

    Bouncer::refresh();
}

/**
 * Whether an authority holds an ability, asked the way the application asks it.
 *
 * @param  class-string|null  $entity
 */
function holds(Illuminate\Database\Eloquent\Model $authority, string $ability, ?string $entity = null): bool
{
    return Bouncer::getClipboard()->check($authority, $ability, $entity);
}

/**
 * Signs in somebody who may work the roles screen.
 *
 * That screen is governed by abilities like everything else, so reaching it at all takes
 * a grant. Almost every screen test needs this before it can get to what it is testing.
 */
function signInAsRoleManager(): User
{
    $user = signIn();

    /** @var class-string $role */
    $role = Silber\Bouncer\Database\Models::classname(Silber\Bouncer\Database\Role::class);

    grant($user, [
        ['viewAny', $role],
        ['view', $role],
        ['create', $role],
        ['update', $role],
        ['delete', $role],
    ]);

    return $user;
}

/**
 * The nested array the grid holds, read off the component.
 *
 * There is no field per cell any more — the grid is one field carrying the whole array —
 * so `assertFormSet`, which resolves a field per path, has nothing to resolve. This is
 * the same array the save walks, which makes it the stronger thing to assert against:
 * a cell missing from here is a cell nobody can write.
 *
 * @param  Livewire\Features\SupportTesting\Testable<Livewire\Component>  $component
 * @return array<string, array<string, string>>
 */
function gridState(Livewire\Features\SupportTesting\Testable $component): array
{
    /** @var array<string, array<string, string>> $state */
    $state = $component->get('data.'.ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm::ABILITIES);

    return $state;
}

/**
 * The cells the grid offers, as paths.
 *
 * There is no field per cell any more — the grid is one field holding the whole nested
 * array — so what a cell being offered means is that the narrowed catalogue put it in
 * that array. It is the same array the save walks, which makes this the stronger check
 * of the two: a cell nobody can see is also a cell nobody can write.
 *
 * @param  array<string, mixed>  $state
 * @return array<int, string>
 */
function offeredCells(array $state): array
{
    $paths = [];

    foreach ($state as $subject => $actions) {
        foreach (array_keys((array) $actions) as $action) {
            $paths[] = $subject.'.'.$action;
        }
    }

    return $paths;
}

/**
 * How many abilities a role holds, counted through the pivot rather than a relation the
 * analyser cannot see on whatever model the application configured.
 */
function abilityCount(Illuminate\Database\Eloquent\Model $role): int
{
    return Silber\Bouncer\Database\Models::ability()->newQuery()
        ->join(
            Silber\Bouncer\Database\Models::table('permissions'),
            Silber\Bouncer\Database\Models::table('permissions').'.ability_id',
            '=',
            Silber\Bouncer\Database\Models::table('abilities').'.id',
        )
        ->where(Silber\Bouncer\Database\Models::table('permissions').'.entity_id', $role->getKey())
        ->where(Silber\Bouncer\Database\Models::table('permissions').'.entity_type', $role->getMorphClass())
        ->count();
}
