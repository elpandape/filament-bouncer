<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function abilityStore(): AbilityStore
{
    return app(AbilityStore::class);
}

/**
 * The row a write left behind, found by the two columns that make up its identity.
 *
 * A null entity type is asked for as a null rather than left open, because the same
 * action is stored twice over — once about a model and once about nothing at all — and a
 * lookup that matched either would silently test the wrong row.
 */
function storedAbility(string $name, ?string $entityType = null): StoredAbility
{
    $query = Models::ability()->newQuery()->where('name', $name);

    if ($entityType === null) {
        $query->whereNull('entity_type');
    } else {
        $query->where('entity_type', $entityType);
    }

    return $query->firstOrFail();
}

/**
 * How many queries a call runs.
 *
 * A call with nothing to do returns the same either way, so counting queries is the only
 * way to tell that it stayed out of the database instead of asking about an empty set.
 *
 * @param  Closure(): void  $work
 */
function queriesWhile(Closure $work): int
{
    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $work();

    return $queries;
}

test('the reconciliation speaks for a plain ability row', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);

    expect(abilityStore()->speaksFor(storedAbility('view', Post::class)))->toBeTrue();
});

test('it does not speak for an ability about one record', function (): void {
    Bouncer::allow('editor')->to('view', Post::query()->create());

    expect(abilityStore()->speaksFor(storedAbility('view', Post::class)))->toBeFalse();
});

test('it does not speak for an ability covering only what its holder owns', function (): void {
    Bouncer::allow('editor')->toOwn(Post::class)->to('update');

    expect(abilityStore()->speaksFor(storedAbility('update', Post::class)))->toBeFalse();
});

test('it does not speak for the wildcard a blanket grant leaves behind', function (): void {
    Bouncer::allow('editor')->everything();

    expect(abilityStore()->speaksFor(storedAbility('*', '*')))->toBeFalse();
});

test('it does not speak for a grant covering a whole model', function (): void {
    Bouncer::allow('editor')->toManage(Post::class);

    expect(abilityStore()->speaksFor(storedAbility('*', Post::class)))->toBeFalse();
});

test('it does not speak for an action aimed at every model at once', function (): void {
    Bouncer::allow('editor')->to('view', '*');

    expect(abilityStore()->speaksFor(storedAbility('view', '*')))->toBeFalse();
});

test('it stops speaking for a row once the row is gone', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);

    $ability = storedAbility('view', Post::class);
    abilityStore()->delete([$ability]);

    expect(abilityStore()->speaksFor($ability))->toBeFalse();
});

test('a row about one record is restricted', function (): void {
    Bouncer::allow('editor')->to('view', Post::query()->create());

    expect(abilityStore()->isRestricted(storedAbility('view', Post::class)))->toBeTrue();
});

test('a row covering only what its holder owns is restricted', function (): void {
    Bouncer::allow('editor')->toOwn(Post::class)->to('update');

    expect(abilityStore()->isRestricted(storedAbility('update', Post::class)))->toBeTrue();
});

test('a plain row is not restricted', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);

    expect(abilityStore()->isRestricted(storedAbility('view', Post::class)))->toBeFalse();
});

test('the wildcard a blanket grant leaves behind is not restricted, only unspoken for', function (): void {
    Bouncer::allow('editor')->everything();

    $wildcard = storedAbility('*', '*');

    expect(abilityStore()->isRestricted($wildcard))->toBeFalse()
        ->and(abilityStore()->speaksFor($wildcard))->toBeFalse();
});

test('a row and the declaration that would create it answer to one identity', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);

    expect(abilityStore()->identity(storedAbility('view', Post::class)))
        ->toBe(Ability::forModel(Post::class, 'view', 'Posts: View', AbilityScope::Read)->identity());
});

test('the same action about a model and about nothing gets two identities', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);
    Bouncer::allow('editor')->to('view');

    $store = abilityStore();

    expect($store->identity(storedAbility('view')))
        ->toBe(Ability::identityFor('view', null))
        ->not->toBe($store->identity(storedAbility('view', Post::class)));
});

test('the catalogued rows are keyed by identity', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);

    expect(abilityStore()->catalogued())->toHaveKey(Ability::identityFor('view', Post::class));
});

test('it leaves out every kind of row the catalogue never declares', function (): void {
    Bouncer::allow('editor')->to('create', Post::class);
    Bouncer::allow('editor')->to('view', Post::query()->create());
    Bouncer::allow('editor')->toOwn(Post::class)->to('update');
    Bouncer::allow('editor')->everything();
    Bouncer::allow('editor')->to('delete', '*');

    expect(array_keys(abilityStore()->catalogued()))
        ->toBe([Ability::identityFor('create', Post::class)]);
});

test('it writes down the row a declaration describes', function (): void {
    abilityStore()->create([Ability::forModel(Post::class, 'view', 'Posts: View', AbilityScope::Read)]);

    $stored = storedAbility('view', Post::class);

    expect($stored->getAttribute('title'))->toBe('Posts: View')
        ->and($stored->getAttribute('entity_id'))->toBeNull()
        ->and($stored->getAttribute('only_owned'))->toBeFalse()
        ->and(abilityStore()->speaksFor($stored))->toBeTrue();
});

test('creating nothing goes nowhere near the database', function (): void {
    expect(queriesWhile(static function (): void {
        abilityStore()->create([]);
    }))->toBe(0);
});

test('deleting an ability takes with it every grant that pointed at it', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);

    abilityStore()->delete([storedAbility('view', Post::class)]);

    expect(Models::ability()->newQuery()->count())->toBe(0)
        ->and(DB::table('permissions')->count())->toBe(0);
});

test('it deletes only the rows it was handed', function (): void {
    Bouncer::allow('editor')->to('view', Post::class);
    Bouncer::allow('editor')->to('create', Post::class);

    abilityStore()->delete([storedAbility('view', Post::class)]);

    expect(Models::ability()->newQuery()->count())->toBe(1)
        ->and(Models::ability()->newQuery()->where('name', 'create')->exists())->toBeTrue();
});

test('deleting nothing goes nowhere near the database', function (): void {
    expect(queriesWhile(static function (): void {
        abilityStore()->delete([]);
    }))->toBe(0);
});
