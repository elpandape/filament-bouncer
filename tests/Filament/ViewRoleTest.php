<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
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

test('the record is read in the shape it is written in, filled and out of reach', function (): void {
    signInAsRoleManager();

    $role = readRole();
    grant($role, [['viewAny', Post::class]]);

    $page = livewire(ViewRole::class, ['record' => $role->getKey()]);

    /** @var array<string, array<string, string>> $state */
    $state = $page->get('data.'.RoleForm::ABILITIES);

    expect($state[Subject::keyFor(Post::class)]['viewAny'] ?? null)->toBe(Stance::Granted->value);

    $page->assertSeeHtml('class="fb-seg"')->assertSeeHtml('disabled="disabled"');
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
