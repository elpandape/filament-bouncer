<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

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

test('the record is read in the shape it is written in, filled and out of reach', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    $page = livewire(ViewRole::class, ['record' => $role->getKey()]);

    /** @var array<string, array<string, string>> $state */
    $state = $page->get('data.'.RoleForm::ABILITIES);

    expect($state[Subject::keyFor(Post::class)]['viewAny'] ?? null)->toBe(Stance::Granted->value);

    $page->assertSeeHtml('class="fb-seg"')->assertSeeHtml('disabled: true');
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

test('how far the role reaches is read before a single row of it is', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSeeHtml('fb-cov-lg')
        ->assertSeeHtml('data-granted="1"');
});

test('a role reaching everything through the wildcard reads full', function (): void {
    signInAsRoleManager();

    $role = readRole();
    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSeeHtml('data-reaches-all="true"')
        ->assertSee(__('filament-bouncer::roles.table.reaches_all'));
});

test('the identity is read as entries, not as inputs waiting for nobody', function (): void {
    signInAsRoleManager();

    $role = readRole();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSee(__('filament-bouncer::roles.record.identity'))
        ->assertSee(__('filament-bouncer::roles.record.holders'))
        ->assertSee(__('filament-bouncer::roles.record.updated'))
        ->assertSee(__('filament-bouncer::roles.record.created'));
});

test('the reach bar of the record page reads its three figures out in words', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSee(trans_choice('filament-bouncer::roles.coverage.granted', 1))
        ->assertSee(__('filament-bouncer::roles.coverage.neutral'));
});

test('a reading page offers no save inside the summary bar', function (): void {
    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole()->getKey()])
        ->assertDontSeeHtml('fb-summary-save');
});

test('a role that forbids nothing says so, and says why it matters', function (): void {
    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole()->getKey()])
        ->assertSee(__('filament-bouncer::roles.record.forbidden_empty'))
        ->assertSee(__('filament-bouncer::roles.record.forbidden_note'));
});

test('a denial is listed on the card with its action and its subject', function (): void {
    signInAsRoleManager();

    $role = readRole();
    Bouncer::forbid($role)->to('delete', Post::class);
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSeeHtml('fb-badge-dng')
        ->assertDontSee(__('filament-bouncer::roles.record.forbidden_empty'));
});

test('a denial covering the whole model is listed as the manage row, not as a method name', function (): void {
    signInAsRoleManager();

    $role = readRole();
    Bouncer::forbid($role)->to(Ability::MANAGE_NAME, Post::class);
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSeeHtmlInOrder([
            'fb-badge-dng',
            __('filament-bouncer::roles.form.manage'),
            'fb-forbidden-subject',
        ]);
});

test('the people holding the role are on its page', function (): void {
    signInAsRoleManager();

    $role = readRole();
    $holder = readHolder();

    Bouncer::assign('editor')->to($holder);
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertSee('Killa')
        ->assertSee('killa@example.test')
        ->assertSee(__('filament-bouncer::roles.record.retract'));
});

test('a role nobody holds says so instead of drawing an empty list', function (): void {
    signInAsRoleManager();

    livewire(ViewRole::class, ['record' => readRole()->getKey()])
        ->assertSee(__('filament-bouncer::roles.record.holders_empty'));
});

test('taking the role away goes through the store and off the account', function (): void {
    signInAsRoleManager();

    $role = readRole();
    $holder = readHolder();

    Bouncer::assign('editor')->to($holder);
    Bouncer::refresh();

    readRetractArmedByHand($role, $holder);

    expect(readHolds($role, $holder))->toBeFalse();
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

test('the way back in still comes off an account that is not its last holder', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    signInAsRoleManager();

    $role = readRole('super-admin');
    $first = readHolder();
    $second = readHolder('Amaru', 'amaru@example.test');

    Bouncer::assign('super-admin')->to($first);
    Bouncer::assign('super-admin')->to($second);
    Bouncer::refresh();

    readRetractArmedByHand($role, $first);

    expect(readHolds($role, $first))->toBeFalse()
        ->and(readHolds($role, $second))->toBeTrue();
});
