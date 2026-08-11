<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Comment;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function listedAbility(string $name, ?string $entityType = null): Model
{
    /** @var Model $row */
    $row = Models::ability()->newQuery()
        ->where('name', $name)
        ->where('entity_type', $entityType)
        ->whereNull('entity_id')
        ->where('only_owned', false)
        ->firstOrFail();

    return $row;
}

function abilityOwnedRow(): Model
{
    Bouncer::allow('reviewer')->toOwn(Post::class)->to('delete');

    /** @var Model $row */
    $row = Models::ability()->newQuery()->where('only_owned', true)->firstOrFail();

    return $row;
}

function abilityHolderRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

test('the screen lists the rules the store holds', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ListAbilities::class)
        ->assertCanSeeTableRecords([listedAbility('update', Post::class), listedAbility('viewAny', Post::class)]);
});

test('a rule carries the words it is called by beside its name', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ListAbilities::class)->assertSee('Posts: Change');
});

test('the listing names the model each rule decides about', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ListAbilities::class)
        ->assertSee('Posts')
        ->assertSee(__('filament-bouncer::abilities.form.no_entity'));
});

test('it gathers the rules under the thing they decide about', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $table = AbilityResource::table(Filament\Tables\Table::make(new ListAbilities));

    expect($table->getDefaultGroup()?->getId())->toBe('entity_type')
        ->and(array_keys($table->getGroups()))->toBe(['entity_type']);
});

test('a rule the code declares is told apart from one nobody declares', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    Bouncer::allow('reviewer')->to('sing-a-song');

    expect(Declaration::of(listedAbility('update', Post::class)))->toBe(Declaration::Declared)
        ->and(Declaration::of(listedAbility('sing-a-song')))->toBe(Declaration::Drifted);

    livewire(ListAbilities::class)
        ->assertSee(__('filament-bouncer::abilities.declared.declared'))
        ->assertSee(__('filament-bouncer::abilities.declared.drifted'));
});

test('a narrowed rule is told apart from one nobody declares', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $owned = abilityOwnedRow();

    expect(Declaration::of($owned))->toBe(Declaration::Apart);

    livewire(ListAbilities::class)
        ->assertSee(__('filament-bouncer::abilities.declared.apart'))
        ->assertSee(Reach::Owned->label());
});

test('the listing says who holds a rule, and which of the two things they say', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $granted = abilityHolderRole();
    $denied = abilityHolderRole('reviewer');

    grant($granted, [['update', Post::class]]);
    Bouncer::forbid($denied)->to('update', Post::class);
    Bouncer::refresh();

    $labels = app(Labels::class);

    livewire(ListAbilities::class)
        ->assertSee(__('filament-bouncer::abilities.table.holder', [
            'role' => 'editor',
            'stance' => $labels->stance(Stance::Granted),
        ]))
        ->assertSee(__('filament-bouncer::abilities.table.holder', [
            'role' => 'reviewer',
            'stance' => $labels->stance(Stance::Forbidden),
        ]));
});

test('a rule about one record names the record it is about', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $post = Post::query()->create();

    Bouncer::allow('reviewer')->to('delete', $post);

    /** @var int|string $key */
    $key = $post->getKey();

    livewire(ListAbilities::class)
        ->assertSee(__('filament-bouncer::abilities.reach.record_reading', ['id' => (string) $key]));
});

test('the actions lead to pages of their own instead of opening a modal', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $row = listedAbility('update', Post::class);

    livewire(ListAbilities::class)
        ->assertActionHasUrl(TestAction::make('view')->table($row), AbilityResource::getUrl('view', ['record' => $row]))
        ->assertActionHasUrl(TestAction::make('edit')->table($row), AbilityResource::getUrl('edit', ['record' => $row]));
});

test('the screen offers narrowing a rule and nothing else', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ListAbilities::class)
        ->assertActionVisible(TestAction::make('create'))
        ->assertSee(__('filament-bouncer::abilities.narrow'));
});

test('no row offers being taken away', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ListAbilities::class)
        ->assertActionVisible(TestAction::make('view')->table(listedAbility('update', Post::class)))
        ->assertActionDoesNotExist(TestAction::make('delete')->table(listedAbility('update', Post::class)));
});

test('a rule about a model the catalogue never keyed reads as the row itself', function (): void {
    signInAsAbilityManager();

    Bouncer::allow('reviewer')->to('view', Comment::class);

    livewire(ListAbilities::class)->assertSee(Comment::class);
});

test('the screen is closed to somebody the abilities do not name', function (): void {
    signIn();

    livewire(ListAbilities::class)->assertForbidden();
});
