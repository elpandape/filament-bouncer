<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Widgets\RoleStats;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function countedRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function declaresRoleStats(): void
{
    Filament::getPanel('test')->widgets([RoleStats::class]);

    app(CatalogRegistry::class)->forget();
}

test('it counts the roles that have been composed', function (): void {
    signInAsRoleManager();

    countedRole();
    countedRole('reviewer');

    livewire(RoleStats::class)
        ->assertSee(__('filament-bouncer::roles.stats.roles'))
        ->assertSee('2');
});

test('it counts what the panel is able to ask about', function (): void {
    signInAsRoleManager();

    $declared = count(app(CatalogRegistry::class)->current()->abilities());

    livewire(RoleStats::class)
        ->assertSee(__('filament-bouncer::roles.stats.abilities'))
        ->assertSee((string) $declared);
});

test('a denial in force is counted apart and shown in red', function (): void {
    signInAsRoleManager();

    $role = countedRole();
    Bouncer::forbid($role)->to('delete', Post::class);
    Bouncer::refresh();

    livewire(RoleStats::class)
        ->assertSee(__('filament-bouncer::roles.stats.forbidden'))
        ->assertSeeHtml('fi-color-danger');
});

test('no denial at all is not shouted about', function (): void {
    signInAsRoleManager();

    countedRole();

    livewire(RoleStats::class)->assertDontSeeHtml('fi-color-danger');
});

test('it counts the accounts that hold no role at all', function (): void {
    $reader = signInAsRoleManager();

    signIn();

    countedRole();
    Bouncer::assign('editor')->to($reader);
    Bouncer::refresh();

    livewire(RoleStats::class)
        ->assertSee(__('filament-bouncer::roles.stats.unassigned'))
        ->assertSee('1');
});

test('the figures are out of sight for somebody never granted them', function (): void {
    declaresRoleStats();
    signIn();

    expect(RoleStats::canView())->toBeFalse();
});

test('the figures appear for somebody holding the ability that names them', function (): void {
    declaresRoleStats();
    grant(signIn(), [['widget:'.Subject::keyFor(RoleStats::class), null]]);

    expect(RoleStats::canView())->toBeTrue();
});
