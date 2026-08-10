<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

/**
 * @param  array<string, bool|string>  $options
 */
function reconcile(array $options = []): string
{
    Artisan::call('filament-bouncer:reconcile', $options);

    return Artisan::output();
}

/**
 * @return array<int, string>
 */
function storedNames(): array
{
    /** @var array<int, string> $names */
    $names = Models::ability()->newQuery()->orderBy('name')->pluck('name')->all();

    return $names;
}

test('it writes down every ability the catalogue declares', function (): void {
    reconcile();

    expect(storedNames())->toBe([
        'create',
        'delete',
        'forceDelete',
        'impersonate-users',
        'page:elpandape-filamentbouncer-tests-fixtures-filament-pages-settings',
        'update',
        'view',
        'viewAny',
        'viewAny',
        'widget:elpandape-filamentbouncer-tests-fixtures-filament-widgets-activity',
        'widget:elpandape-filamentbouncer-tests-fixtures-filament-widgets-stats',
    ]);
});

test('an ability carries the title the catalogue gave it', function (): void {
    reconcile();

    /** @var string $title */
    $title = Models::ability()->newQuery()->where('name', 'forceDelete')->value('title');

    expect($title)->toBe('Posts: Force Delete');
});

test('running it again writes nothing', function (): void {
    reconcile();

    expect(reconcile())->toContain('Created 0 abilities.')
        ->and(Models::ability()->newQuery()->count())->toBe(11);
});

test('it leaves an ability the catalogue no longer declares in place', function (): void {
    Bouncer::allow('editor')->to('sing-a-song');

    expect(reconcile())->toContain('Left 1 ability in place')
        ->and(Models::ability()->newQuery()->where('name', 'sing-a-song')->exists())->toBeTrue();
});

test('pruning deletes it, and with it the grant that pointed at it', function (): void {
    Bouncer::allow('editor')->to('sing-a-song');

    expect(reconcile(['--prune' => true]))->toContain('Deleted 1 ability')
        ->and(Models::ability()->newQuery()->where('name', 'sing-a-song')->exists())->toBeFalse()
        ->and(DB::table('permissions')->count())->toBe(0);
});

test('it never touches an ability about one record, one owner, or everything', function (): void {
    $post = Post::query()->create();

    Bouncer::allow('editor')->to('view', $post);
    Bouncer::allow('editor')->toOwn(Post::class)->to('update');
    Bouncer::allow('editor')->everything();

    reconcile(['--prune' => true]);

    expect(Models::ability()->newQuery()->whereNotNull('entity_id')->count())->toBe(1)
        ->and(Models::ability()->newQuery()->where('only_owned', true)->count())->toBe(1)
        ->and(Models::ability()->newQuery()->where('entity_type', '*')->count())->toBe(1);
});

test('checking reports both sides of the difference and fails', function (): void {
    Bouncer::allow('editor')->to('sing-a-song');

    $status = Artisan::call('filament-bouncer:reconcile', ['--check' => true]);
    $output = Artisan::output();

    expect($status)->toBe(1)
        ->and($output)->toContain('Missing from the store')
        ->and($output)->toContain('viewAny on '.Post::class)
        ->and($output)->toContain('Stored but no longer declared')
        ->and($output)->toContain('sing-a-song')
        ->and(Models::ability()->newQuery()->where('name', 'viewAny')->exists())->toBeFalse();
});

test('checking passes once the store matches the catalogue', function (): void {
    reconcile();

    $status = Artisan::call('filament-bouncer:reconcile', ['--check' => true]);

    expect($status)->toBe(0)
        ->and(Artisan::output())->toContain('The store matches the catalogue.');
});

test('it accepts the panel to walk on the command line', function (): void {
    reconcile(['--panel' => 'test']);

    expect(Models::ability()->newQuery()->count())->toBe(11);
});

test('it refuses a panel that does not exist', function (): void {
    expect(static fn (): string => reconcile(['--panel' => 'nope']))
        ->toThrow(InvalidArgumentException::class, 'There is no Filament panel with the id [nope].');
});

test('a catalogue of a hundred abilities is written in a single insert', function (): void {
    config()->set('filament-bouncer.custom', array_fill_keys(
        array_map(static fn (int $index): string => 'custom-ability-'.$index, range(1, 100)),
        'write',
    ));

    $inserts = 0;

    DB::listen(function (QueryExecuted $query) use (&$inserts): void {
        if (str_starts_with($query->sql, 'insert')) {
            $inserts++;
        }
    });

    reconcile();

    expect($inserts)->toBe(1)
        ->and(Models::ability()->newQuery()->count())->toBe(110);
});

test('a stored row and its declaration answer to the same identity', function (): void {
    reconcile();

    $store = app(AbilityStore::class);

    $stored = Models::ability()->newQuery()
        ->where('name', 'viewAny')
        ->where('entity_type', Post::class)
        ->firstOrFail();

    expect($store->catalogued())->toHaveKey($store->identity($stored));
});
