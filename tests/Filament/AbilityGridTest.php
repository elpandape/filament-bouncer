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
 * @return array{key: string, label: string, class: string|null, policy: string|null, icon: string|null, action: string|null, cells: array<string, bool>}|null
 */
function gridRowFor(string $key, string $tab = 'subjects'): ?array
{
    return collect(gridField()->getSections()[$tab]['rows'] ?? [])->firstWhere('key', $key);
}

/**
 * The field as the host mounted it, which is the only way to ask it about a record.
 */
function gridOn(Model $role): AbilityGrid
{
    /** @var GridHost $host */
    $host = livewire(GridHost::class, ['role' => $role->getKey()])->instance();

    foreach ($host->getSchema('form')?->getComponents() ?? [] as $component) {
        if ($component instanceof AbilityGrid) {
            return $component;
        }
    }

    return gridField();
}

/**
 * @return array<string, string>
 */
function gridActions(): array
{
    $columns = gridField()->getColumnGroups();

    return array_column(array_merge([], ...array_column($columns['groups'], 'actions')), 'action', 'action');
}

test('a subject is laid out as one row, and every action it declares gets a cell', function (): void {
    signIn();

    $row = gridRowFor(Subject::keyFor(Post::class));

    expect($row)->not->toBeNull()
        ->and(array_keys($row['cells'] ?? []))->toContain('viewAny', 'view', 'delete');
});

test('the grant covering a whole model is a column of its own', function (): void {
    signIn();

    $columns = gridField()->getColumnGroups();

    expect($columns['manage']['action'])->toBe(Ability::MANAGE_ACTION)
        ->and($columns['manage']['label'])->toBe(__('filament-bouncer::roles.form.manage'))
        ->and(gridActions())->not->toHaveKey(Ability::MANAGE_ACTION);
});

test('the columns come only from the subjects the grid draws', function (): void {
    signIn();

    config()->set('filament-bouncer.custom', ['impersonate-users' => 'write']);
    app(CatalogRegistry::class)->forget();

    expect(gridActions())->not->toHaveKey('use')
        ->and(gridRowFor('impersonate-users', 'custom'))->not->toBeNull();
});

test('a door is not laid out as a grid', function (): void {
    signIn();

    $sections = gridField()->getSections();

    expect($sections['subjects']['grid'])->toBeTrue()
        ->and($sections['pages']['grid'])->toBeFalse()
        ->and($sections['widgets']['grid'])->toBeFalse();
});

test('a door carries the single action it answers, and a gridded subject does not', function (): void {
    signIn();

    $page = gridField()->getSections()['pages']['rows'][0] ?? null;
    $subject = gridRowFor(Subject::keyFor(Post::class));

    expect($page['action'] ?? null)->not->toBeNull()
        ->and($subject)->not->toBeNull()
        ->and($subject === null ? 'a row is missing' : $subject['action'])->toBeNull();
});

test('a section carries the name its tab is called by', function (): void {
    signIn();

    expect(gridField()->getSections()['subjects']['label'])
        ->toBe(__('filament-bouncer::roles.tabs.subjects'));
});

test('reading only is not everything', function (): void {
    signIn();

    $presets = gridField()->getPresets();

    expect($presets[0]['key'])->toBe('read')
        ->and($presets[0]['actions'])->toContain('viewAny', 'view')
        ->and($presets[0]['actions'])->not->toContain('delete')
        ->and($presets[0]['actions'])->not->toContain('forceDelete');
});

test('the corner shortcut aims at the gridded subjects and no others', function (): void {
    signIn();

    config()->set('filament-bouncer.custom', ['impersonate-users' => 'write']);
    app(CatalogRegistry::class)->forget();

    $aimed = gridField()->getGriddedSubjects();

    expect($aimed)->toContain(Subject::keyFor(Post::class))
        ->and($aimed)->not->toContain('impersonate-users');
});

test('a subject names the policy its columns come from', function (): void {
    signIn();

    expect(gridRowFor(Subject::keyFor(Post::class))['policy'] ?? null)->toBe('PostPolicy');

    livewire(GridHost::class)
        ->assertSeeHtml('fb-subject-policy')
        ->assertSee('PostPolicy');
});

test('a subject with no model behind it names no policy and no icon', function (): void {
    signIn();

    config()->set('filament-bouncer.icons', [Post::class => 'heroicon-o-users']);

    $pages = gridField()->getSections()['pages']['rows'];

    expect($pages)->not->toBeEmpty();

    foreach ($pages as $page) {
        expect($page['class'])->toBeNull()
            ->and($page['policy'])->toBeNull()
            ->and($page['icon'])->toBeNull();
    }
});

test('a subject configured an icon draws it', function (): void {
    signIn();

    config()->set('filament-bouncer.icons', [Post::class => 'heroicon-o-users']);

    expect(gridRowFor(Subject::keyFor(Post::class))['icon'] ?? null)->toBe('heroicon-o-users');

    livewire(GridHost::class)->assertSeeHtml('fb-subject-icon');
});

test('a subject nobody configured an icon for draws none', function (): void {
    signIn();

    livewire(GridHost::class)->assertDontSeeHtml('fb-subject-icon');
});

test('the catalogue is drawn as a table of subjects against actions', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb"')
        ->assertSeeHtml('class="fb-table"')
        ->assertSeeHtml('class="fb-corner"')
        ->assertSee(__('filament-bouncer::roles.grid.subject'));
});

test('every group of the catalogue is offered as a tab', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('data-tab="subjects"')
        ->assertSeeHtml('data-tab="pages"')
        ->assertSeeHtml('data-tab="widgets"')
        ->assertSee(__('filament-bouncer::roles.tabs.pages'));
});

test('a cell carries the word of its stance as its accessible name', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb-box"')
        ->assertSeeHtml('data-subject="'.Subject::keyFor(Post::class).'"')
        ->assertSeeHtml('data-action="viewAny"')
        ->assertSeeHtml('aria-label');
});

test('an action a subject does not declare is a gap, and not an empty box', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('fb-cell-void')
        ->assertSee(__('filament-bouncer::roles.grid.undeclared'));
});

test('a shortcut is offered on the row and on the corner, and clearing only on the corner', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb-shortcuts"')
        ->assertSeeHtml('class="fb-shortcuts fb-shortcuts-all"')
        ->assertSee(__('filament-bouncer::roles.grid.preset_read'))
        ->assertSee(__('filament-bouncer::roles.grid.clear'));
});

test('a role holding nothing has no cell reached by anything broader', function (): void {
    signIn();

    expect(gridField()->getBroader())->toBeEmpty();
});

test('the summary of a grid nobody flagged carries no buttons', function (): void {
    signIn();

    livewire(GridHost::class)->assertDontSeeHtml('fb-summary-save');
});

test('a door is drawn as a line, not as a column', function (): void {
    signIn();

    livewire(GridHost::class)
        ->assertSeeHtml('class="fb-doors"')
        ->assertSeeHtml('class="fb-door"');
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

test('a cell reached by a broader rule draws the answer, not an empty box', function (): void {
    signIn();

    $role = gridRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    expect(gridOn($role)->getBroader()[Subject::keyFor(Post::class)]['viewAny'] ?? false)->toBeTrue();
});

test('a cell nothing reaches draws no hollow tick', function (): void {
    signIn();

    expect(array_column(gridOn(gridRole())->getBroader()[Subject::keyFor(Post::class)] ?? [], null))
        ->each->toBeFalse();
});

test('what a cell says beyond its stance is read off the record', function (): void {
    signIn();

    $grid = gridField();

    expect($grid->getNotes())->toBeEmpty()
        ->and($grid->getBroader())->toBeEmpty();
});

test('the legend of the corner mark is drawn only where a cell carries one', function (): void {
    signIn();

    livewire(GridHost::class, ['role' => gridRole()->getKey()])
        ->assertSee(__('filament-bouncer::roles.grid.note_legend'));

    livewire(GridHost::class)
        ->assertDontSee(__('filament-bouncer::roles.grid.note_legend'));
});

test('a cell holding a rule the grid cannot write is marked', function (): void {
    signIn();

    livewire(GridHost::class, ['role' => gridRole()->getKey()])
        ->assertSeeHtml('class="fb-narrowed"');
});
