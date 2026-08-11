<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Policies\AbilityPolicy;
use ElPandaPe\FilamentBouncer\Policies\AbilityRowPolicy;
use ElPandaPe\FilamentBouncer\Policies\RolePolicy;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

pest()->extend(TestCase::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function rolePolicy(): RolePolicy
{
    return app(RolePolicy::class);
}

function abilityRowPolicy(): AbilityRowPolicy
{
    return app(AbilityRowPolicy::class);
}

/**
 * The role model as the application configured it, which is the only name the policy
 * ever asks Bouncer about.
 *
 * @return class-string<Model>
 */
function policyRoleModel(): string
{
    /** @var class-string<Model> $model */
    $model = Models::classname(Role::class);

    return $model;
}

/**
 * @return class-string<Model>
 */
function policyAbilityModel(): string
{
    /** @var class-string<Model> $model */
    $model = Models::classname(Ability::class);

    return $model;
}

function policyRoleRecord(string $name): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function policyAbilityRecord(string $name): Model
{
    /** @var Model $ability */
    $ability = Models::ability()->newQuery()->create(['name' => $name]);

    return $ability;
}

/**
 * The actions a policy declares, read exactly the way the catalogue reads them: every
 * public method that is not static and not one of PHP's own.
 *
 * @param  class-string  $policy
 * @return array<int, string>
 */
function declaredActions(string $policy): array
{
    $actions = [];

    foreach (new ReflectionClass($policy)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isStatic()) {
            continue;
        }

        if (str_starts_with($method->getName(), '__')) {
            continue;
        }

        $actions[] = $method->getName();
    }

    sort($actions);

    return $actions;
}

/*
|--------------------------------------------------------------------------
| The roles policy
|--------------------------------------------------------------------------
*/

test('the roles policy lists roles only for somebody holding viewAny over the role model', function (): void {
    $user = signIn();

    expect(rolePolicy()->viewAny($user))->toBeFalse();

    grant($user, [['viewAny', policyRoleModel()]]);

    expect(rolePolicy()->viewAny($user))->toBeTrue();
});

test('the roles policy opens creating a role only to somebody holding create over the role model', function (): void {
    $user = signIn();

    expect(rolePolicy()->create($user))->toBeFalse();

    grant($user, [['create', policyRoleModel()]]);

    expect(rolePolicy()->create($user))->toBeTrue();
});

test('the roles policy shows a role only to somebody holding view over it', function (): void {
    $user = signIn();
    $role = policyRoleRecord('editor');

    expect(rolePolicy()->view($user, $role))->toBeFalse();

    grant($user, [['view', policyRoleModel()]]);

    expect(rolePolicy()->view($user, $role))->toBeTrue();
});

test('the roles policy opens editing a role only to somebody holding update over it', function (): void {
    $user = signIn();
    $role = policyRoleRecord('editor');

    expect(rolePolicy()->update($user, $role))->toBeFalse();

    grant($user, [['update', policyRoleModel()]]);

    expect(rolePolicy()->update($user, $role))->toBeTrue();
});

test('the roles policy opens deleting a role only to somebody holding delete over it', function (): void {
    $user = signIn();
    $role = policyRoleRecord('editor');

    expect(rolePolicy()->delete($user, $role))->toBeFalse();

    grant($user, [['delete', policyRoleModel()]]);

    expect(rolePolicy()->delete($user, $role))->toBeTrue();
});

test('a grant naming one role does not reach another through the roles policy', function (): void {
    $user = signIn();
    $mine = policyRoleRecord('editor');
    $other = policyRoleRecord('auditor');

    Bouncer::allow($user)->to('delete', $mine);
    Bouncer::refresh();

    expect(rolePolicy()->delete($user, $mine))->toBeTrue()
        ->and(rolePolicy()->delete($user, $other))->toBeFalse();
});

test('a grant naming one role does not open creating roles at all', function (): void {
    $user = signIn();

    Bouncer::allow($user)->to('create', policyRoleRecord('editor'));
    Bouncer::refresh();

    expect(rolePolicy()->create($user))->toBeFalse();
});

test('a forbidden ability shuts the roles policy on somebody who was granted it too', function (): void {
    $user = signIn();
    $role = policyRoleRecord('editor');

    grant($user, [['update', policyRoleModel()]]);

    expect(rolePolicy()->update($user, $role))->toBeTrue();

    Bouncer::forbid($user)->to('update', policyRoleModel());
    Bouncer::refresh();

    expect(rolePolicy()->update($user, $role))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The abilities policy
|--------------------------------------------------------------------------
*/

test('the abilities policy lists rows only for somebody holding viewAny over the ability model', function (): void {
    $user = signIn();

    expect(abilityRowPolicy()->viewAny($user))->toBeFalse();

    grant($user, [['viewAny', policyAbilityModel()]]);

    expect(abilityRowPolicy()->viewAny($user))->toBeTrue();
});

test('the abilities policy opens narrowing an ability only to somebody holding create over the ability model', function (): void {
    $user = signIn();

    expect(abilityRowPolicy()->create($user))->toBeFalse();

    grant($user, [['create', policyAbilityModel()]]);

    expect(abilityRowPolicy()->create($user))->toBeTrue();
});

test('the abilities policy shows a row only to somebody holding view over it', function (): void {
    $user = signIn();
    $ability = policyAbilityRecord('browse');

    expect(abilityRowPolicy()->view($user, $ability))->toBeFalse();

    grant($user, [['view', policyAbilityModel()]]);

    expect(abilityRowPolicy()->view($user, $ability))->toBeTrue();
});

test('the abilities policy opens retitling a row only to somebody holding update over it', function (): void {
    $user = signIn();
    $ability = policyAbilityRecord('browse');

    expect(abilityRowPolicy()->update($user, $ability))->toBeFalse();

    grant($user, [['update', policyAbilityModel()]]);

    expect(abilityRowPolicy()->update($user, $ability))->toBeTrue();
});

test('a grant naming one ability row does not reach another through the abilities policy', function (): void {
    $user = signIn();
    $mine = policyAbilityRecord('browse');
    $other = policyAbilityRecord('publish');

    Bouncer::allow($user)->to('update', $mine);
    Bouncer::refresh();

    expect(abilityRowPolicy()->update($user, $mine))->toBeTrue()
        ->and(abilityRowPolicy()->update($user, $other))->toBeFalse();
});

test('a forbidden ability shuts the abilities policy on somebody who was granted it too', function (): void {
    $user = signIn();
    $ability = policyAbilityRecord('browse');

    grant($user, [['view', policyAbilityModel()]]);

    expect(abilityRowPolicy()->view($user, $ability))->toBeTrue();

    Bouncer::forbid($user)->to('view', policyAbilityModel());
    Bouncer::refresh();

    expect(abilityRowPolicy()->view($user, $ability))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The two screens are two doors
|--------------------------------------------------------------------------
*/

test('holding every ability of the roles screen leaves the abilities screen shut', function (): void {
    $user = signInAsRoleManager();

    expect(rolePolicy()->viewAny($user))->toBeTrue()
        ->and(abilityRowPolicy()->viewAny($user))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The registration the service provider makes
|--------------------------------------------------------------------------
*/

test('the gate hands the role model to the packaged policy', function (): void {
    $user = signIn();

    grant($user, [['viewAny', policyRoleModel()]]);

    expect(Gate::getPolicyFor(policyRoleModel()))->toBeInstanceOf(RolePolicy::class)
        ->and(Gate::forUser($user)->allows('viewAny', policyRoleModel()))->toBeTrue();
});

test('the gate hands the ability model to the packaged policy', function (): void {
    $user = signIn();
    $ability = policyAbilityRecord('browse');

    grant($user, [['view', policyAbilityModel()]]);

    expect(Gate::getPolicyFor(policyAbilityModel()))->toBeInstanceOf(AbilityRowPolicy::class)
        ->and(Gate::forUser($user)->allows('view', $ability))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| What each policy declares, which is what the catalogue offers
|--------------------------------------------------------------------------
*/

test('the roles policy declares the five actions the screen has and no bulk delete', function (): void {
    expect(declaredActions(RolePolicy::class))->toBe(['create', 'delete', 'update', 'view', 'viewAny']);
});

test('the abilities policy declares four actions and no way to take a row away', function (): void {
    expect(declaredActions(AbilityRowPolicy::class))->toBe(['create', 'update', 'view', 'viewAny']);
});

test('the base policy carries no actions of its own', function (): void {
    expect(declaredActions(AbilityPolicy::class))->toBeEmpty();
});
