<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\ViewRole;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

test('the detail screen shows the same grid, filled and out of reach', function (): void {
    $post = Subject::keyFor(Post::class);

    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    $role = Models::role()->newQuery()->create(['name' => 'editor']);
    grant($role, [['create', Post::class]]);

    livewire(ViewRole::class, ['record' => $role->getKey()])
        ->assertFormSet([
            "abilities.{$post}.viewAny" => false,
            "abilities.{$post}.create" => true,
        ])
        ->assertFormFieldDisabled("abilities.{$post}.create")
        ->assertOk();
});
