<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\CatalogTab;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Catalog\EntityKind;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources\PostResource;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Activity;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Widgets\Stats;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Comment;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Tag;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Facades\Filament;

pest()->extend(TestCase::class);

function catalog(): Catalog
{
    return app(CatalogRegistry::class)->get(Filament::getDefaultPanel());
}

test('a resource contributes the actions its policy is prepared to answer', function (): void {
    $entity = catalog()->entity(Entity::keyFor(Post::class));

    expect($entity)->toBeInstanceOf(Entity::class)
        ->and($entity?->kind)->toBe(EntityKind::Resource)
        ->and($entity?->entityType)->toBe(Post::class)
        ->and(array_keys($entity->abilities ?? []))
        ->toBe(['create', 'delete', 'forceDelete', 'update', 'view', 'viewAny']);
});

test('a policy hook, a static helper and a constructor are not actions', function (): void {
    $entity = catalog()->entity(Entity::keyFor(Post::class));

    expect(array_keys($entity->abilities ?? []))
        ->not->toContain('before')
        ->not->toContain('shouldAudit')
        ->not->toContain('__construct');
});

test('a resource whose model has no policy contributes nothing', function (): void {
    expect(catalog()->entity(Entity::keyFor(Comment::class)))->toBeNull();
});

test('a model named in configuration joins the catalogue without a resource', function (): void {
    $entity = catalog()->entity(Entity::keyFor(Tag::class));

    expect($entity?->kind)->toBe(EntityKind::Model)
        ->and(array_keys($entity->abilities ?? []))->toBe(['viewAny']);
});

test('a page contributes a single ability naming the page', function (): void {
    $entity = catalog()->entity(Entity::keyFor(Settings::class));

    expect($entity?->kind)->toBe(EntityKind::Page)
        ->and($entity?->entityType)->toBeNull()
        ->and($entity?->ability(Ability::ACCESS_ACTION)?->name)
        ->toBe('page:'.Entity::keyFor(Settings::class));
});

test('a widget registered plainly and one registered through make both reach the catalogue', function (): void {
    expect(catalog()->entity(Entity::keyFor(Stats::class))?->kind)->toBe(EntityKind::Widget)
        ->and(catalog()->entity(Entity::keyFor(Activity::class))?->ability(Ability::ACCESS_ACTION)?->name)
        ->toBe('widget:'.Entity::keyFor(Activity::class));
});

test('an ability declared in configuration reaches the catalogue under its own name', function (): void {
    $entity = catalog()->entity('impersonate-users');

    expect($entity?->kind)->toBe(EntityKind::Custom)
        ->and($entity?->ability(Ability::CUSTOM_ACTION)?->name)->toBe('impersonate-users')
        ->and($entity?->ability(Ability::CUSTOM_ACTION)?->scope)->toBe(AbilityScope::Write);
});

test('a model ability is stored against the model and a component ability against nothing', function (): void {
    $post = catalog()->entity(Entity::keyFor(Post::class))?->ability('viewAny');
    $page = catalog()->entity(Entity::keyFor(Settings::class))?->ability(Ability::ACCESS_ACTION);

    expect($post?->attributes())->toBe([
        'name' => 'viewAny',
        'title' => 'Posts: See the list',
        'entity_id' => null,
        'entity_type' => Post::class,
        'only_owned' => false,
    ])->and($page?->attributes()['entity_type'])->toBeNull();
});

test('columns run from the lightest scope to the heaviest', function (): void {
    expect(catalog()->actions)->toBe([
        'view' => AbilityScope::Read,
        'viewAny' => AbilityScope::Read,
        'create' => AbilityScope::Write,
        'update' => AbilityScope::Write,
        'use' => AbilityScope::Write,
        'delete' => AbilityScope::Withdraw,
        'forceDelete' => AbilityScope::Irreversible,
    ]);
});

test('rows run resources first and pages before widgets', function (): void {
    expect(array_column(array_map(
        static fn (Entity $entity): array => ['kind' => $entity->kind->value],
        array_values(catalog()->entities),
    ), 'kind'))->toBe(['resource', 'resource', 'resource', 'model', 'page', 'widget', 'widget', 'custom']);
});

test('an ignored component leaves the catalogue', function (): void {
    config()->set('filament-bouncer.ignore', [PostResource::class, Settings::class, Stats::class]);
    app(CatalogRegistry::class)->forget();

    expect(catalog()->entity(Entity::keyFor(Post::class)))->toBeNull()
        ->and(catalog()->entity(Entity::keyFor(Settings::class)))->toBeNull()
        ->and(catalog()->entity(Entity::keyFor(Stats::class)))->toBeNull();
});

test('the catalogue is built once per panel', function (): void {
    expect(catalog())->toBe(catalog());
});

test('a catalogue with nothing in it knows so', function (): void {
    expect(new Catalog([], [])->isEmpty())->toBeTrue()
        ->and(catalog()->isEmpty())->toBeFalse();
});

test('the catalogue divides into tabs, and each kind knows which one it is read in', function (): void {
    $tabs = catalog()->tabs();

    expect(array_keys($tabs))->toBe([
        CatalogTab::Entities->value,
        CatalogTab::Pages->value,
        CatalogTab::Widgets->value,
        CatalogTab::Custom->value,
    ])
        ->and($tabs[CatalogTab::Entities->value])->toHaveKey(Entity::keyFor(Post::class))
        ->and($tabs[CatalogTab::Pages->value])->toHaveKey(Entity::keyFor(Settings::class))
        ->and($tabs[CatalogTab::Widgets->value])->toHaveKey(Entity::keyFor(Stats::class))
        ->and($tabs[CatalogTab::Custom->value])->toHaveKey('impersonate-users')
        ->and(CatalogTab::Entities->isGrid())->toBeTrue()
        ->and(CatalogTab::Pages->isGrid())->toBeFalse();
});

test('a tab with nothing to show never appears', function (): void {
    $entity = new Entity('post', 'Post', EntityKind::Resource, null, []);
    $catalog = new Catalog([$entity->key => $entity], []);

    expect(array_keys($catalog->tabs()))->toBe([CatalogTab::Entities->value]);
});
