<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ListRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Tables\RolesTable;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function listedRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name, 'title' => 'What '.$name.' may do']);

    return $role;
}

function deleteArmedByHand(Model $role): void
{
    /** @var int|string $key */
    $key = $role->getKey();

    livewire(ListRoles::class)
        ->call('mountAction', 'delete', [], ['table' => true, 'recordKey' => (string) $key])
        ->call('callMountedAction');
}

function stillStanding(Model $role): bool
{
    return Models::role()->newQuery()->whereKey($role->getKey())->exists();
}

function listedCatalogCells(): int
{
    return collect(app(CatalogRegistry::class)->current()->subjects)
        ->sum(static fn (Subject $subject): int => count($subject->cells()));
}

test('the screen lists the roles that exist', function (): void {
    signInAsRoleManager();

    $editor = listedRole();
    $reviewer = listedRole('reviewer');

    livewire(ListRoles::class)->assertCanSeeTableRecords([$editor, $reviewer]);
});

test('a role carries the words it is called by beside its name', function (): void {
    signInAsRoleManager();

    listedRole();

    livewire(ListRoles::class)->assertSee('What editor may do');
});

test('the row of the role that holds everything offers neither editing nor deleting', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $privileged = listedRole('super-admin');
    $ordinary = listedRole('editor');

    livewire(ListRoles::class)
        ->assertActionHidden(TestAction::make('edit')->table($privileged))
        ->assertActionHidden(TestAction::make('delete')->table($privileged))
        ->assertActionVisible(TestAction::make('edit')->table($ordinary))
        ->assertActionVisible(TestAction::make('delete')->table($ordinary));
});

test('the row of a role the reader holds offers neither either', function (): void {
    $reader = signInAsRoleManager();

    $mine = listedRole();
    $somebody = listedRole('reviewer');

    Bouncer::assign('editor')->to($reader);
    Bouncer::refresh();

    livewire(ListRoles::class)
        ->assertActionHidden(TestAction::make('edit')->table($mine))
        ->assertActionHidden(TestAction::make('delete')->table($mine))
        ->assertActionVisible(TestAction::make('edit')->table($somebody));
});

test('the rows nobody works on from here say so with a padlock', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $reader = signInAsRoleManager();

    $privileged = listedRole('super-admin');
    $mine = listedRole('editor');
    $ordinary = listedRole('reviewer');

    Bouncer::assign('editor')->to($reader);
    Bouncer::refresh();

    livewire(ListRoles::class)
        ->assertActionVisible(TestAction::make('locked')->table($privileged))
        ->assertActionVisible(TestAction::make('locked')->table($mine))
        ->assertActionHidden(TestAction::make('locked')->table($ordinary));
});

test('the listing announces itself and offers searching by name or title', function (): void {
    signInAsRoleManager();

    livewire(ListRoles::class)
        ->assertSee(__('filament-bouncer::roles.list.subtitle'))
        ->assertSee(__('filament-bouncer::roles.table.search'));
});

test('the foot of the table names the catalogue every bar is drawn against', function (): void {
    signInAsRoleManager();

    listedRole();

    livewire(ListRoles::class)
        ->assertSeeHtml('fb-catalog-legend')
        ->assertSee(__('filament-bouncer::roles.table.legend', ['total' => listedCatalogCells()]));
});

test('a row still offers being read when it may not be changed', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    livewire(ListRoles::class)
        ->assertActionVisible(TestAction::make('view')->table(listedRole('super-admin')));
});

test('the actions lead to pages of their own instead of opening a modal', function (): void {
    signInAsRoleManager();

    $role = listedRole();

    livewire(ListRoles::class)
        ->assertActionHasUrl(TestAction::make('view')->table($role), RoleResource::getUrl('view', ['record' => $role]))
        ->assertActionHasUrl(TestAction::make('edit')->table($role), RoleResource::getUrl('edit', ['record' => $role]));
});

test('the reach bar reads what the role holds', function (): void {
    signInAsRoleManager();

    $role = listedRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ListRoles::class)
        ->assertSeeHtml('data-granted="1"')
        ->assertSeeHtml('data-forbidden="0"')
        ->assertSeeHtml('data-reaches-all="false"');
});

test('the reach bar counts a denial apart from a grant', function (): void {
    signInAsRoleManager();

    $role = listedRole();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::forbid($role)->to('delete', Post::class);
    Bouncer::refresh();

    livewire(ListRoles::class)
        ->assertSeeHtml('data-granted="1"')
        ->assertSeeHtml('data-forbidden="1"');
});

test('a role reaching everything through the wildcard is drawn full and says so', function (): void {
    signInAsRoleManager();

    $role = listedRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    livewire(ListRoles::class)
        ->assertSeeHtml('data-reaches-all="true"')
        ->assertSee(__('filament-bouncer::roles.table.reaches_all'));
});

test('a role says how many accounts hold it', function (): void {
    $reader = signInAsRoleManager();

    $role = listedRole();
    Bouncer::assign('editor')->to($reader);
    Bouncer::refresh();

    livewire(ListRoles::class)
        ->assertSee(__('filament-bouncer::roles.table.holders'))
        ->assertSee('1');
});

test('the screen offers composing a role of its own', function (): void {
    signInAsRoleManager();

    livewire(ListRoles::class)
        ->assertActionVisible(TestAction::make('create'));
});

test('the screen carries the figures its rows cannot show', function (): void {
    signInAsRoleManager();

    livewire(ListRoles::class)->assertSeeHtml('filament.widgets.role-stats');
});

test('a delete armed by hand takes away the role whose row offers one', function (): void {
    signInAsRoleManager();

    $ordinary = listedRole();

    deleteArmedByHand($ordinary);

    expect(stillStanding($ordinary))->toBeFalse();
});

test('a delete armed by hand leaves a role the reader holds standing', function (): void {
    $reader = signInAsRoleManager();

    $mine = listedRole();
    Bouncer::assign('editor')->to($reader);
    Bouncer::refresh();

    deleteArmedByHand($mine);

    expect(stillStanding($mine))->toBeTrue();
});

test('a delete armed by hand leaves the way back in standing', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $privileged = listedRole('super-admin');

    deleteArmedByHand($privileged);

    expect(stillStanding($privileged))->toBeTrue();
});

test('the table offers nothing to a selection', function (): void {
    signInAsRoleManager();

    $table = RolesTable::configure(Table::make(new ListRoles));

    expect($table->getToolbarActions())->toBeEmpty()
        ->and($table->isSelectionEnabled())->toBeFalse();
});
