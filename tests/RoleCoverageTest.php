<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function coveredRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function coverageOf(Model $role): RoleCoverage
{
    return RoleCoverage::for($role, app(CatalogRegistry::class)->current());
}

test('a role holding nothing says nothing about anything', function (): void {
    signIn();

    $coverage = coverageOf(coveredRole());

    expect($coverage->granted)->toBe(0)
        ->and($coverage->forbidden)->toBe(0)
        ->and($coverage->neutral)->toBe($coverage->total)
        ->and($coverage->total)->toBeGreaterThan(0)
        ->and($coverage->reachesAll)->toBeFalse();
});

test('it counts a grant and a denial apart', function (): void {
    signIn();

    $role = coveredRole();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::forbid($role)->to('delete', Post::class);
    Bouncer::refresh();

    $coverage = coverageOf($role);

    expect($coverage->granted)->toBe(1)
        ->and($coverage->forbidden)->toBe(1)
        ->and($coverage->neutral)->toBe($coverage->total - 2);
});

test('the three answers always add up to the catalogue', function (): void {
    signIn();

    $role = coveredRole();
    grant($role, [['viewAny', Post::class], ['create', Post::class]]);

    $coverage = coverageOf($role);

    expect($coverage->granted + $coverage->forbidden + $coverage->neutral)->toBe($coverage->total);
});

test('the wildcard reaches everything without a rule of its own', function (): void {
    signIn();

    $role = coveredRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    $coverage = coverageOf($role);

    expect($coverage->reachesAll)->toBeTrue()
        ->and($coverage->granted)->toBe(0)
        ->and($coverage->neutral)->toBe($coverage->total);
});

test('a role reaching some of it but not the rest is not reaching everything', function (): void {
    signIn();

    $role = coveredRole();
    Bouncer::allow($role)->toManage(Post::class);
    Bouncer::refresh();

    expect(coverageOf($role)->reachesAll)->toBeFalse();
});
