<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Facades\Filament;
use Silber\Bouncer\BouncerFacade as Bouncer;

pest()->extend(TestCase::class);

function whole(): Catalog
{
    return app(CatalogRegistry::class)->get(Filament::getDefaultPanel());
}

test('nobody signed in may decide about anything', function (): void {
    expect(app(EditableCatalog::class)->current()->isEmpty())->toBeTrue();
});

test('it narrows the catalogue to what the authority holds', function (): void {
    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    $subject = app(EditableCatalog::class)->current()->subject(Subject::keyFor(Post::class));

    expect(array_keys($subject->abilities ?? []))->toBe(['create', 'viewAny']);
});

test('a subject holding nothing leaves the grid altogether', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    expect(app(EditableCatalog::class)->current()->subject('impersonate-users'))->toBeNull();
});

test('an ability that has been forbidden does not count as held', function (): void {
    $user = signIn();

    grant($user, [['viewAny', Post::class], ['create', Post::class]]);
    Bouncer::forbid($user)->to('create', Post::class);
    Bouncer::refresh();

    $subject = app(EditableCatalog::class)->current()->subject(Subject::keyFor(Post::class));

    expect(array_keys($subject->abilities ?? []))->toBe(['viewAny']);
});

test('the columns that survive keep the order the catalogue laid out', function (): void {
    grant(signIn(), [['forceDelete', Post::class], ['viewAny', Post::class]]);

    expect(array_keys(app(EditableCatalog::class)->current()->actions))->toBe(['viewAny', 'forceDelete'])
        ->and(array_keys(whole()->actions))->toContain('viewAny');
});

test('the wildcard hands over the whole catalogue', function (): void {
    $user = signIn();

    Bouncer::allow($user)->everything();
    Bouncer::refresh();

    expect(array_keys(app(EditableCatalog::class)->current()->subjects))
        ->toBe(array_keys(whole()->subjects));
});
