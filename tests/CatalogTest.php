<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Catalog\SubjectKind;
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
    $subject = catalog()->subject(Subject::keyFor(Post::class));

    expect($subject)->toBeInstanceOf(Subject::class)
        ->and($subject?->kind)->toBe(SubjectKind::Resource)
        ->and($subject?->entityType)->toBe(Post::class)
        ->and(array_keys($subject->abilities ?? []))
        ->toBe(['create', 'delete', 'forceDelete', 'update', 'view', 'viewAny']);
});

test('a policy hook, a static helper and a constructor are not actions', function (): void {
    $subject = catalog()->subject(Subject::keyFor(Post::class));

    expect(array_keys($subject->abilities ?? []))
        ->not->toContain('before')
        ->not->toContain('shouldAudit')
        ->not->toContain('__construct');
});

test('a resource whose model has no policy contributes nothing', function (): void {
    expect(catalog()->subject(Subject::keyFor(Comment::class)))->toBeNull();
});

test('a model named in configuration joins the catalogue without a resource', function (): void {
    $subject = catalog()->subject(Subject::keyFor(Tag::class));

    expect($subject?->kind)->toBe(SubjectKind::Model)
        ->and(array_keys($subject->abilities ?? []))->toBe(['viewAny']);
});

test('a page contributes a single ability naming the page', function (): void {
    $subject = catalog()->subject(Subject::keyFor(Settings::class));

    expect($subject?->kind)->toBe(SubjectKind::Page)
        ->and($subject?->entityType)->toBeNull()
        ->and($subject?->ability(Ability::ACCESS_ACTION)?->name)
        ->toBe('page:'.Subject::keyFor(Settings::class));
});

test('a widget registered plainly and one registered through make both reach the catalogue', function (): void {
    expect(catalog()->subject(Subject::keyFor(Stats::class))?->kind)->toBe(SubjectKind::Widget)
        ->and(catalog()->subject(Subject::keyFor(Activity::class))?->ability(Ability::ACCESS_ACTION)?->name)
        ->toBe('widget:'.Subject::keyFor(Activity::class));
});

test('an ability declared in configuration reaches the catalogue under its own name', function (): void {
    $subject = catalog()->subject('impersonate-users');

    expect($subject?->kind)->toBe(SubjectKind::Custom)
        ->and($subject?->ability(Ability::CUSTOM_ACTION)?->name)->toBe('impersonate-users')
        ->and($subject?->ability(Ability::CUSTOM_ACTION)?->scope)->toBe(AbilityScope::Write);
});

test('a model ability is stored against the model and a component ability against nothing', function (): void {
    $post = catalog()->subject(Subject::keyFor(Post::class))?->ability('viewAny');
    $page = catalog()->subject(Subject::keyFor(Settings::class))?->ability(Ability::ACCESS_ACTION);

    expect($post?->attributes())->toBe([
        'name' => 'viewAny',
        'title' => 'Posts: View Any',
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
        static fn (Subject $subject): array => ['kind' => $subject->kind->value],
        array_values(catalog()->subjects),
    ), 'kind'))->toBe(['resource', 'model', 'page', 'widget', 'widget', 'custom']);
});

test('an ignored component leaves the catalogue', function (): void {
    config()->set('filament-bouncer.ignore', [PostResource::class, Settings::class, Stats::class]);
    app(CatalogRegistry::class)->forget();

    expect(catalog()->subject(Subject::keyFor(Post::class)))->toBeNull()
        ->and(catalog()->subject(Subject::keyFor(Settings::class)))->toBeNull()
        ->and(catalog()->subject(Subject::keyFor(Stats::class)))->toBeNull();
});

test('the catalogue is built once per panel', function (): void {
    expect(catalog())->toBe(catalog());
});

test('a catalogue with nothing in it knows so', function (): void {
    expect(new Catalog([], [])->isEmpty())->toBeTrue()
        ->and(catalog()->isEmpty())->toBeFalse();
});
