<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Store\Restriction;
use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

test('a restriction starts holding neither an owned grant nor any record', function (): void {
    $restriction = new Restriction;

    expect($restriction->owned)->toBeFalse()
        ->and($restriction->records)->toBe(0);
});

test('withOwned adds the owned grant without dropping the records already counted', function (): void {
    $restriction = new Restriction(records: 2)->withOwned();

    expect($restriction->owned)->toBeTrue()
        ->and($restriction->records)->toBe(2);
});

test('withOwned twice still reads as one owned grant', function (): void {
    $restriction = (new Restriction)->withOwned()->withOwned();

    expect($restriction->owned)->toBeTrue()
        ->and($restriction->records)->toBe(0);
});

test('withRecord counts one more record without dropping the owned grant', function (): void {
    $restriction = new Restriction(owned: true)->withRecord();

    expect($restriction->owned)->toBeTrue()
        ->and($restriction->records)->toBe(1);
});

test('records add up one call at a time', function (): void {
    $restriction = (new Restriction)->withRecord()->withRecord()->withRecord();

    expect($restriction->records)->toBe(3);
});

test('the two of them accumulate in either order', function (): void {
    $owned = (new Restriction)->withOwned()->withRecord();
    $recorded = (new Restriction)->withRecord()->withOwned();

    expect($owned->owned)->toBeTrue()
        ->and($owned->records)->toBe(1)
        ->and($recorded->owned)->toBeTrue()
        ->and($recorded->records)->toBe(1);
});

test('neither call changes the restriction it was asked of', function (): void {
    $original = new Restriction;

    $owned = $original->withOwned();
    $recorded = $original->withRecord();

    expect($owned)->not->toBe($original)
        ->and($recorded)->not->toBe($original)
        ->and($original->owned)->toBeFalse()
        ->and($original->records)->toBe(0);
});
