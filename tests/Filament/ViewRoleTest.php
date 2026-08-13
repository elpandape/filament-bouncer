<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Infolists\AbilityTags;
use ElPandaPe\FilamentBouncer\Filament\Infolists\OrphanChips;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function readRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function readHolder(string $name = 'Killa', string $email = 'killa@example.test'): User
{
    return User::forceCreate([
        'name' => $name,
        'email' => $email,
        'password' => 'irrelevant',
    ]);
}

function readRetractArmedByHand(Model $role, mixed $holder): void
{
    if ($holder instanceof Model) {
        $holder = $holder->getKey();
    }

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->call('mountAction', 'retractRole', ['holder' => $holder])
        ->call('callMountedAction');
}

function readHolds(Model $role, Model $holder): bool
{
    return Illuminate\Support\Facades\DB::table(Models::table('assigned_roles'))
        ->where('role_id', $role->getKey())
        ->where('entity_id', $holder->getKey())
        ->where('entity_type', $holder->getMorphClass())
        ->exists();
}

test('the record says what the role says, and not a grid nobody can touch', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSeeHtml('class="fb-tg"')
        ->assertSeeHtml('fb-tg-tag-granted')
        ->assertDontSeeHtml('class="fb-table"');
});

test('an action nobody said anything about is left off the reading', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: function (AbilityTags $entry): bool {
            $rows = array_column($entry->getRows(), 'tags', 'key');
            $actions = array_column($rows[Subject::keyFor(Post::class)] ?? [], 'action');

            return $actions === ['viewAny'];
        });
});

test('what the role is silent about is named at the foot', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: fn (AbilityTags $entry): bool => $entry->getSilent() !== []);
});

test('a role saying nothing at all says so', function (): void {
    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole()->getKey()])
        ->assertSee(__('filament-bouncer::roles.record.tags_empty'));
});

test('only the rules the next sync would take away are counted', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::allow($role)->to('archive', Post::class);
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('orphans', checkComponentUsing: function (OrphanChips $entry): bool {
            $doomed = $entry->getDoomed();

            return $entry->getCount() === 1 && ($doomed[0]['action'] ?? '') === 'archive';
        })
        ->assertSee(__('filament-bouncer::roles.record.orphans_some'));
});

test('a role with nothing to lose says so', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('orphans', checkComponentUsing: fn (OrphanChips $entry): bool => $entry->isClean())
        ->assertSee(__('filament-bouncer::roles.record.orphans_none'));
});

test('a denial is shown as a denial and not as an absence', function (): void {
    signInAsRoleManager();

    $role = readRole();
    Bouncer::forbid($role)->to('delete', Post::class);
    Bouncer::refresh();

    /** @var array<string, array<string, string>> $state */
    $state = livewire(ViewRole::class, ['record' => $role->getKey()])->get('data.'.RoleForm::ABILITIES);

    expect($state[Subject::keyFor(Post::class)]['delete'] ?? null)->toBe(Stance::Forbidden->value);
});

test('the record of a role nobody may work on offers no way in', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole('super-admin')->getKey()])
        ->assertActionHidden(TestAction::make('edit'));
});

test('the record of an ordinary role offers the way in', function (): void {
    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole()->getKey()])
        ->assertActionVisible(TestAction::make('edit'));
});

test('the identity is read as entries, and the tenant beside it', function (): void {
    signInAsRoleManager();

    $role = readRole();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSee(__('filament-bouncer::roles.record.identity'))
        ->assertSee(__('filament-bouncer::roles.record.scope'))
        ->assertSee(__('filament-bouncer::roles.record.metadata'))
        ->assertSee(__('filament-bouncer::roles.record.updated'))
        ->assertSee(__('filament-bouncer::roles.record.created'));
});

test('a reading page offers no save inside the summary bar', function (): void {
    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole()->getKey()])
        ->assertDontSeeHtml('fb-summary-save');
});

test('a retract armed against nobody changes nothing', function (): void {
    signInAsRoleManager();

    $role = readRole();
    $holder = readHolder();

    Bouncer::assign('editor')->to($holder);
    Bouncer::refresh();

    readRetractArmedByHand($role, 999999);

    expect(readHolds($role, $holder))->toBeTrue();
});

test('the last holder of the way back in keeps it, even against a request armed by hand', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $role = readRole('super-admin');
    $holder = readHolder();

    Bouncer::assign('super-admin')->to($holder);
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertDontSee(__('filament-bouncer::roles.record.retract'));

    readRetractArmedByHand($role, $holder);

    expect(readHolds($role, $holder))->toBeTrue();
});

test('a narrowed rule names the record it reaches, and the one it owns says so', function (): void {
    signInAsRoleManager();

    $role = readRole();
    $post = Post::query()->create(['title' => 'Quipu']);

    Bouncer::allow($role)->to('view', $post);
    Bouncer::allow($role)->toOwn(Post::class)->to('update');
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: function (AbilityTags $entry): bool {
            $narrowed = array_column($entry->getNarrowed(), null, 'action');

            $view = $narrowed['view'] ?? null;
            $update = $narrowed['update'] ?? null;

            return $view !== null && array_column($view['records'], 'title') === ['Quipu']
                && $update !== null && $update['owned'] && $update['records'] === [];
        })
        ->assertSee(__('filament-bouncer::roles.record.narrowed_heading'))
        ->assertSee(__('filament-bouncer::roles.record.owned'));
});

test('a narrowed rule whose record is gone says so instead of hiding it', function (): void {
    signInAsRoleManager();

    $role = readRole();
    $post = Post::query()->create(['title' => 'Quipu']);

    Bouncer::allow($role)->to('view', $post);
    Bouncer::refresh();

    $post->delete();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: function (AbilityTags $entry): bool {
            $records = array_merge([], ...array_column($entry->getNarrowed(), 'records'));

            return count($records) === 1 && $records[0]['missing'];
        })
        ->assertSee(__('filament-bouncer::roles.record.record_gone'));
});

test('an entry has nothing to draw before it is put in a schema', function (): void {
    signInAsRoleManager();

    expect(AbilityTags::make('abilities')->getRows())->toBeEmpty()
        ->and(AbilityTags::make('abilities')->getNarrowed())->toBeEmpty()
        ->and(AbilityTags::make('abilities')->getStances())->toBeEmpty()
        ->and(OrphanChips::make('orphans')->getDoomed())->toBeEmpty()
        ->and(OrphanChips::make('orphans')->getGroups())->toBeEmpty();
});

test('what the role is silent about is spelled out while the names still fit', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);
    Bouncer::allow($role)->to('viewAny', Models::classname(Role::class));
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: fn (AbilityTags $entry): bool => $entry->spellsSilent())
        ->assertSee(__('filament-bouncer::roles.record.and'));
});

test('a door the role reaches is read apart from the subjects', function (): void {
    signInAsRoleManager();

    $role = readRole();

    Bouncer::allow($role)->to('page:'.Subject::keyFor(Settings::class));
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: function (AbilityTags $entry): bool {
            $doors = array_column($entry->getDoors(), 'rows', 'tab');

            return array_column($doors['pages'] ?? [], 'key') === [Subject::keyFor(Settings::class)];
        })
        ->assertSee(__('filament-bouncer::roles.tabs.pages'));
});

test('what the role reaches through a broader rule is read as reached', function (): void {
    signInAsRoleManager();

    $role = readRole();

    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSchemaComponentExists('abilities', checkComponentUsing: function (AbilityTags $entry): bool {
            $stances = $entry->getStances()[Subject::keyFor(Post::class)] ?? [];

            return ($stances['viewAny'] ?? '') === 'broader';
        });
});
