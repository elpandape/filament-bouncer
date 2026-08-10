<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Pages\Abilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function reader(): void
{
    grant(signIn(), [
        ['page:'.Subject::keyFor(Abilities::class), null],
        ['viewAny', Post::class],
        ['delete', Post::class],
    ]);
}

test('the screen takes a grant of its own to reach, like everything else', function (): void {
    signIn();

    expect(Abilities::canAccess())->toBeFalse();

    reader();

    expect(Abilities::canAccess())->toBeTrue();
});

/**
 * How every role stands on one ability of the gridded tab, keyed by role name.
 *
 * @return array<string, array{role: string, stance: string, how: string|null}>
 */
function holdersOf(string $name): array
{
    /** @var Abilities $page */
    $page = livewire(Abilities::class)->instance();

    $holders = [];

    foreach ($page->getTabs()['subjects']['abilities'] as $ability) {
        if ($ability['name'] !== $name) {
            continue;
        }

        foreach ($ability['holders'] as $holder) {
            $holders[$holder['role']] = $holder;
        }
    }

    return $holders;
}

test('it tells a grant somebody made from one the role merely fell into', function (): void {
    reader();

    $directo = Models::role()->newQuery()->create(['name' => 'directo']);
    Bouncer::allow($directo)->to('viewAny', Post::class);

    $amplio = Models::role()->newQuery()->create(['name' => 'amplio']);
    Bouncer::allow($amplio)->toManage(Post::class);
    Bouncer::refresh();

    $holders = holdersOf('viewAny');

    expect($holders['directo']['stance'])->toBe(Stance::Granted->value)
        ->and($holders['directo']['how'])->toBe('direct')
        ->and($holders['amplio']['stance'])->toBe(Stance::Granted->value)
        ->and($holders['amplio']['how'])->toBe('broader');
});

test('a role that no rule reaches is reported as holding nothing', function (): void {
    reader();

    Models::role()->newQuery()->create(['name' => 'vacio']);
    Bouncer::refresh();

    expect(holdersOf('delete')['vacio']['how'])->toBeNull();
});

test('the screen takes its place in the panel from configuration', function (): void {
    config()->set('filament-bouncer.abilities.icon', 'heroicon-o-key');
    config()->set('filament-bouncer.abilities.sort', 7);
    config()->set('filament-bouncer.abilities.slug', 'seguridad/habilidades');
    config()->set('filament-bouncer.navigation.group', 'Seguridad');

    expect(Abilities::getNavigationIcon())->toBe('heroicon-o-key')
        ->and(Abilities::getNavigationSort())->toBe(7)
        ->and(Abilities::getSlug())->toBe('seguridad/habilidades')
        ->and(Abilities::getNavigationGroup())->toBe('Seguridad')
        ->and(Abilities::getNavigationLabel())->toBe(__('filament-bouncer::abilities.title'));
});
