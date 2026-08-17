<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Events\AbilityRef;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

afterEach(function (): void {
    Relation::morphMap([], false);
    Relation::requireMorphMap(false);
});

test('an ability is the same rule read from the catalogue and read from its stored row', function (): void {
    Relation::enforceMorphMap(['post' => Post::class]);

    $catalogued = Ability::forModel(Post::class, 'view', 'Posts: View', AbilityScope::Read);

    $row = Models::ability();
    $row->forceFill($catalogued->attributes())->save();

    expect(AbilityRef::fromCatalog($catalogued)->identity())
        ->toBe(AbilityRef::fromRow($row)->identity())
        ->and(AbilityRef::fromCatalog($catalogued)->entityMorphClass)->toBe('post')
        ->and(AbilityRef::fromRow($row)->entityMorphClass)->toBe('post');
});

test('an ability fenced to one record says so, and carries the words a reader needs', function (): void {
    $row = Models::ability();
    $row->forceFill([
        'name' => 'view',
        'title' => 'Posts: View',
        'entity_type' => Post::class,
        'entity_id' => 7,
        'only_owned' => true,
    ])->save();

    $ref = AbilityRef::fromRow($row);

    expect($ref->entityId)->toBe(7)
        ->and($ref->onlyOwned)->toBeTrue()
        ->and($ref->title)->toBe('Posts: View')
        ->and($ref->describe())->toBe('view on '.Post::class);
});

test('an ability about no model at all is described by its bare name', function (): void {
    $row = Models::ability();
    $row->forceFill(['name' => 'impersonate-users', 'title' => ''])->save();

    $ref = AbilityRef::fromRow($row);

    expect($ref->entityMorphClass)->toBeNull()
        ->and($ref->title)->toBeNull()
        ->and($ref->describe())->toBe('impersonate-users');
});
