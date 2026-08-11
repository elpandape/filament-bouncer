<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\GridHost;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function gridRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function gridField(): AbilityGrid
{
    return AbilityGrid::make('abilities')->catalog(app(CatalogRegistry::class)->current());
}

/**
 * @return array<int, array{action: string, label: string, scope: string, note: string|null, broader: bool}>
 */
function gridRowsFor(string $key): array
{
    /** @var array<int, array{action: string, label: string, scope: string, note: string|null, broader: bool}> $rows */
    $rows = gridField()->getSections()['subjects']['subjects'][$key]['rows'] ?? [];

    return $rows;
}

test('a subject is laid out as rows, and every action it declares gets one', function (): void {
    signIn();

    $rows = gridRowsFor(Subject::keyFor(Post::class));

    expect($rows)->not->toBeEmpty()
        ->and(array_column($rows, 'action'))->toContain('viewAny', 'view', 'delete');
});

test('an action carries the weight the catalogue gave it', function (): void {
    signIn();

    $rows = collect(gridRowsFor(Subject::keyFor(Post::class)));

    expect($rows->firstWhere('action', 'viewAny')['scope'] ?? null)->toBe('read')
        ->and($rows->firstWhere('action', 'delete')['scope'] ?? null)->toBe('withdraw');
});

test('the grant covering a whole model comes first, under a key of its own', function (): void {
    signIn();

    $rows = gridRowsFor(Subject::keyFor(Post::class));

    expect($rows[0]['action'])->toBe(Ability::MANAGE_ACTION)
        ->and($rows[0]['label'])->toBe(__('filament-bouncer::roles.form.manage'));
});

test('a door is not laid out as a grid', function (): void {
    signIn();

    $sections = gridField()->getSections();

    expect($sections['subjects']['doors'])->toBeFalse()
        ->and($sections['pages']['doors'])->toBeTrue()
        ->and($sections['widgets']['doors'])->toBeTrue();
});

test('a section carries the name its tab is called by', function (): void {
    signIn();

    expect(gridField()->getSections()['subjects']['label'])
        ->toBe(__('filament-bouncer::roles.tabs.subjects'));
});

test('reading only is not everything', function (): void {
    signIn();

    $presets = gridField()->getPresets();

    expect($presets['read'])->toContain('viewAny', 'view')
        ->and($presets['read'])->not->toContain('delete')
        ->and($presets['read'])->not->toContain('forceDelete');
});

test('a subject says which policy answers for it', function (): void {
    signIn();

    expect(gridField()->getSections()['subjects']['subjects'][Subject::keyFor(Post::class)]['policy'])
        ->toBe('PostPolicy');
});

test('a role holding nothing has no row reached by anything broader', function (): void {
    signIn();

    expect(array_column(gridRowsFor(Subject::keyFor(Post::class)), 'broader'))
        ->each->toBeFalse();
});

test('the catalogue is drawn as folded sections with a preset each', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb"')
        ->assertSeeHtml('fb-subject-head')
        ->assertSeeHtml('fb-preset')
        ->assertSee(__('filament-bouncer::roles.presets.read'))
        ->assertSee(__('filament-bouncer::roles.presets.all'))
        ->assertSee(__('filament-bouncer::roles.presets.none'));
});

test('each stance is its own button, and its word is its accessible name', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb-seg"')
        ->assertSeeHtml('aria-pressed')
        ->assertSeeHtml(__('filament-bouncer::stances.granted'))
        ->assertSeeHtml(__('filament-bouncer::stances.neutral'))
        ->assertSeeHtml(__('filament-bouncer::stances.forbidden'));
});

test('an action shows the weight it carries', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('fb-weight-read')
        ->assertSeeHtml('fb-weight-withdraw');
});

test('what it all adds up to stays in sight', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb-summary"')
        ->assertSeeHtml(trans_choice('filament-bouncer::roles.summary.granted', 1));
});

test('a door is drawn as a line, not as a fold', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('fb-door')
        ->assertSee(__('filament-bouncer::roles.tabs.pages'));
});

test('the state the grid fills is the shape the store reads', function (): void {
    signIn();

    /** @var array<string, array<string, string>> $state */
    $state = livewire(GridHost::class)->get('data.abilities');

    expect($state[Subject::keyFor(Post::class)]['viewAny'] ?? null)->toBe('neutral');
});

test('a panel that declares nothing says so instead of drawing an empty grid', function (): void {
    signIn();

    livewire(GridHost::class, ['barren' => true])
        ->assertSee(__('filament-bouncer::roles.form.empty'))
        ->assertDontSeeHtml('class="fb-summary"');
});

test('the grid arrives holding what the role was granted', function (): void {
    signIn();

    $role = gridRole();
    grant($role, [['viewAny', Post::class]]);

    /** @var array<string, array<string, string>> $state */
    $state = livewire(GridHost::class, ['role' => $role->getKey()])->get('data.abilities');

    expect($state[Subject::keyFor(Post::class)]['viewAny'] ?? null)->toBe('granted')
        ->and($state[Subject::keyFor(Post::class)]['create'] ?? null)->toBe('neutral');
});

test('a row reached by a broader rule draws the answer, not the dash', function (): void {
    signIn();

    $role = gridRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    livewire(GridHost::class, ['role' => $role->getKey()])
        ->assertSeeHtml('fb-seg-broader');
});

test('a row nothing reaches draws no hollow tick', function (): void {
    signIn();

    livewire(GridHost::class, ['role' => gridRole()->getKey()])
        ->assertDontSeeHtml('fb-seg-broader');
});

test('what a row says beyond its stance is read off the record', function (): void {
    signIn();

    $grid = gridField();

    expect($grid->getNotes())->toBeEmpty()
        ->and($grid->getBroader())->toBeEmpty();
});
