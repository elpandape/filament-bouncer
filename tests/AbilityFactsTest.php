<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Support\AbilityFacts;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

function factsRow(string $name): Model
{
    /** @var Model $row */
    $row = Models::ability()->newQuery()->where('name', $name)->firstOrFail();

    return $row;
}

test('the wildcard reads as the words for managing everything, not as an asterisk', function (): void {
    Bouncer::allow('reviewer')->everything();

    $facts = AbilityFacts::of(factsRow('*'));

    expect($facts->actionLabel)->toBe(__('filament-bouncer::roles.form.manage'))
        ->and($facts->actionName)->toBe('*');
});

test('a plain rule wears grey and a narrowed one the informational blue', function (): void {
    Bouncer::allow('reviewer')->to('update', Post::class);
    Bouncer::allow('reviewer')->toOwn(Post::class)->to('delete');

    /** @var Model $owned */
    $owned = Models::ability()->newQuery()->where('only_owned', true)->firstOrFail();

    expect(AbilityFacts::of(factsRow('update'))->reachColor())->toBe('gray')
        ->and(AbilityFacts::of($owned)->reachColor())->toBe('info')
        ->and(AbilityFacts::of($owned)->reach)->toBe(Reach::Owned);
});
