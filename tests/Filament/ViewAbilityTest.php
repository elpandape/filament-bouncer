<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ViewAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function readAbilityRow(string $name, ?string $entityType = null): Model
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

function readAbilityRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name, 'title' => 'What '.$name.' may do']);

    return $role;
}

test('a rule is read in the shape it is changed in, filled and out of reach', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = readAbilityRole();
    $row = readAbilityRow('update', Post::class);

    grant($role, [['update', Post::class]]);

    $page = livewire(ViewAbility::class, ['record' => $row->getKey()]);

    /** @var array<string, string> $state */
    $state = $page->get('data.'.AbilityForm::HOLDERS);

    /** @var int|string $key */
    $key = $role->getKey();

    expect($state[(string) $key] ?? null)->toBe(Stance::Granted->value);

    $page->assertSeeHtml('class="fb-seg"')->assertSeeHtml('disabled="disabled"');
});

test("the heading is the reconciliation's answer about the row", function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ViewAbility::class, ['record' => readAbilityRow('update', Post::class)->getKey()])
        ->assertSee(__('filament-bouncer::abilities.declared.declared_note'));
});

test('a row nobody declares any more says it is on its way out', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    Bouncer::allow('reviewer')->to('sing-a-song');

    livewire(ViewAbility::class, ['record' => readAbilityRow('sing-a-song')->getKey()])
        ->assertSee(__('filament-bouncer::abilities.declared.drifted_note'));
});

test('a row the reconciliation never spoke for says it is in no danger', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    Bouncer::allow('reviewer')->toOwn(Post::class)->to('delete');

    /** @var Model $owned */
    $owned = Models::ability()->newQuery()->where('only_owned', true)->firstOrFail();

    livewire(ViewAbility::class, ['record' => $owned->getKey()])
        ->assertSee(__('filament-bouncer::abilities.declared.apart_note'));
});

test('how far the rule reaches is read out of the row', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    Bouncer::allow('reviewer')->toOwn(Post::class)->to('delete');

    /** @var Model $owned */
    $owned = Models::ability()->newQuery()->where('only_owned', true)->firstOrFail();

    expect(livewire(ViewAbility::class, ['record' => $owned->getKey()])->get('data.'.AbilityForm::REACH))
        ->toBe(Reach::Owned->label());
});

test('a rule about no model at all says so where the model would be', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $page = livewire(ViewAbility::class, ['record' => readAbilityRow('page:'.Subject::keyFor(Settings::class))->getKey()]);

    expect($page->get('data.entity_type'))->toBe(__('filament-bouncer::abilities.form.no_entity'));
});

test('there is nowhere on the record to ask for the row to be deleted', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ViewAbility::class, ['record' => readAbilityRow('update', Post::class)->getKey()])
        ->assertActionVisible(TestAction::make('edit'))
        ->assertActionDoesNotExist(TestAction::make('delete'));
});

test('a panel with no role composed yet says so instead of drawing an empty list', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(ViewAbility::class, ['record' => readAbilityRow('update', Post::class)->getKey()])
        ->assertSee(__('filament-bouncer::abilities.form.holders_empty'));
});
