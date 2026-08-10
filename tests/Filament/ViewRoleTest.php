<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

test('the detail screen shows the same grid, filled and out of reach', function (): void {
    $post = Subject::keyFor(Post::class);

    grant(signInAsRoleManager(), [['viewAny', Post::class], ['create', Post::class]]);

    $role = Models::role()->newQuery()->create(['name' => 'editor']);
    grant($role, [['create', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertFormSet([
            "abilities.{$post}.viewAny" => Stance::Neutral->value,
            "abilities.{$post}.create" => Stance::Granted->value,
        ])
        ->assertFormFieldDisabled("abilities.{$post}.create")
        ->assertOk();
});

test('the detail screen shows a denial as a denial', function (): void {
    $post = Subject::keyFor(Post::class);

    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = Models::role()->newQuery()->create(['name' => 'editor']);
    Bouncer::forbid($role)->to('viewAny', Post::class);
    Bouncer::refresh();

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertFormSet(["abilities.{$post}.viewAny" => Stance::Forbidden->value]);
});

test('the detail screen of a role nobody may edit offers no way in', function (): void {
    config()->set('filament-bouncer.privileged_role', 'owner');

    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $owner = Models::role()->newQuery()->create(['name' => 'owner']);
    $editor = Models::role()->newQuery()->create(['name' => 'editor']);

    livewire(ViewRole::class, ['record' => $owner->getKey()])
        ->assertActionHidden(TestAction::make('edit'));

    livewire(ViewRole::class, ['record' => $editor->getKey()])
        ->assertActionVisible(TestAction::make('edit'));
});
