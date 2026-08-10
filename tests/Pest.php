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
