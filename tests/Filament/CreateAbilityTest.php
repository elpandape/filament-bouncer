<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\NarrowAbility;
use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Pages\Settings;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

/**
 * @param  array<string, mixed>  $choices
 * @return Testable<Component>
 */
function composed(array $choices): Testable
{
    return livewire(CreateAbility::class)
        ->fillForm($choices + [NarrowAbility::TITLE => 'What this rule is called'])
        ->call('create');
}

/**
 * @return Builder<StoredAbility>
 */
function narrowedRows(): Builder
{
    return Models::ability()->newQuery()->where(static function (Builder $query): void {
        $query->whereNotNull('entity_id')->orWhere('only_owned', true);
    });
}

function composedKey(Model $record): string
{
    /** @var int|string $key */
    $key = $record->getKey();

    return (string) $key;
}

function composedRow(): Model
{
    /** @var Model $row */
    $row = narrowedRows()->sole();

    return $row;
}

function composedCount(): int
{
    return narrowedRows()->count();
}

test('a rule may be held down to what its holder owns', function (): void {
    signInAsAbilityManager();

    composed([
        NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
        NarrowAbility::ACTION => 'update',
        NarrowAbility::REACH => Reach::Owned->value,
    ])->assertHasNoFormErrors();

    $row = composedRow();

    expect($row->getAttribute('name'))->toBe('update')
        ->and($row->getAttribute('entity_type'))->toBe(Post::class)
        ->and($row->getAttribute('only_owned'))->toBeTrue()
        ->and($row->getAttribute('entity_id'))->toBeNull()
        ->and($row->getAttribute('title'))->toBe('What this rule is called');
});

test('a rule may be held down to a single record', function (): void {
    signInAsAbilityManager();

    $post = Post::query()->create();

    composed([
        NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
        NarrowAbility::ACTION => 'delete',
        NarrowAbility::REACH => Reach::Record->value,
        NarrowAbility::RECORD => composedKey($post),
    ])->assertHasNoFormErrors();

    $row = composedRow();

    expect($row->getAttribute('name'))->toBe('delete')
        ->and($row->getAttribute('entity_id'))->toBe($post->getKey())
        ->and($row->getAttribute('only_owned'))->toBeFalse();
});

test('a rule that narrows nothing is refused, because that one belongs to the code', function (): void {
    signInAsAbilityManager();

    composed([
        NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
        NarrowAbility::ACTION => 'update',
        NarrowAbility::REACH => Reach::All->value,
    ])->assertHasFormErrors([NarrowAbility::REACH]);

    expect(composedCount())->toBe(0);
});

test('a second row saying the same thing is refused', function (): void {
    signInAsAbilityManager();

    $choices = [
        NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
        NarrowAbility::ACTION => 'update',
        NarrowAbility::REACH => Reach::Owned->value,
    ];

    composed($choices)->assertHasNoFormErrors();
    composed($choices)->assertHasFormErrors([NarrowAbility::REACH]);

    expect(composedCount())->toBe(1);
});

test('a row about another record is not the same thing', function (): void {
    signInAsAbilityManager();

    $one = Post::query()->create();
    $other = Post::query()->create();

    $choices = [
        NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
        NarrowAbility::ACTION => 'delete',
        NarrowAbility::REACH => Reach::Record->value,
    ];

    composed($choices + [NarrowAbility::RECORD => composedKey($one)])->assertHasNoFormErrors();
    composed($choices + [NarrowAbility::RECORD => composedKey($other)])->assertHasNoFormErrors();

    expect(composedCount())->toBe(2);
});

test("the name is the catalogue's and not the column the cell was chosen in", function (): void {
    signInAsAbilityManager();

    composed([
        NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
        NarrowAbility::ACTION => Ability::MANAGE_ACTION,
        NarrowAbility::REACH => Reach::Owned->value,
    ])->assertHasNoFormErrors();

    expect(composedRow()->getAttribute('name'))->toBe(Ability::MANAGE_NAME)
        ->and(composedRow()->getAttribute('name'))->not->toBe(Ability::MANAGE_ACTION);
});

test('a rule about no model at all is stored under the name the catalogue gives it', function (): void {
    signInAsAbilityManager();

    composed([
        NarrowAbility::SUBJECT => Subject::keyFor(Settings::class),
        NarrowAbility::ACTION => Ability::ACCESS_ACTION,
        NarrowAbility::REACH => Reach::Owned->value,
    ])->assertHasNoFormErrors();

    $row = composedRow();

    expect($row->getAttribute('name'))->toBe('page:'.Subject::keyFor(Settings::class))
        ->and($row->getAttribute('entity_type'))->toBeNull();
});

test('a pair the catalogue does not declare is refused and nothing is written', function (): void {
    signInAsAbilityManager();

    livewire(CreateAbility::class)
        ->fillForm([
            NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
            NarrowAbility::REACH => Reach::Owned->value,
            NarrowAbility::TITLE => 'What this rule is called',
        ])
        ->set('data.'.NarrowAbility::ACTION, 'sing-a-song')
        ->call('create')
        ->assertHasFormErrors([NarrowAbility::ACTION => __('filament-bouncer::abilities.refusals.unknown')]);

    expect(composedCount())->toBe(0);
});

test('the catalogue is what the row is built from, so an unresolvable pair yields none', function (): void {
    expect(NarrowAbility::attributes([
        NarrowAbility::SUBJECT => 'made-up-subject',
        NarrowAbility::ACTION => 'made-up-action',
    ]))->toBeNull();
});

test('a rule about one record needs a model to look the record up in', function (): void {
    signInAsAbilityManager();

    composed([
        NarrowAbility::SUBJECT => Subject::keyFor(Settings::class),
        NarrowAbility::ACTION => Ability::ACCESS_ACTION,
        NarrowAbility::REACH => Reach::Record->value,
        NarrowAbility::RECORD => '1',
    ])->assertHasFormErrors([NarrowAbility::REACH]);

    expect(composedCount())->toBe(0);
});

test('it is composed one question at a time, ending on what is about to be written', function (): void {
    signInAsAbilityManager();

    livewire(CreateAbility::class)
        ->assertSee(__('filament-bouncer::abilities.wizard.ability'))
        ->assertSee(__('filament-bouncer::abilities.wizard.reach'))
        ->assertSee(__('filament-bouncer::abilities.wizard.review'));
});

test('the sentence says nothing has been chosen until something has', function (): void {
    signInAsAbilityManager();

    livewire(CreateAbility::class)
        ->assertSee(__('filament-bouncer::abilities.wizard.nothing'));
});

test('the sentence is recomposed out of the choices as they are made', function (): void {
    signInAsAbilityManager();

    livewire(CreateAbility::class)
        ->fillForm([
            NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
            NarrowAbility::ACTION => 'update',
            NarrowAbility::REACH => Reach::Record->value,
            NarrowAbility::RECORD => '7',
        ])
        ->assertSee(__('filament-bouncer::abilities.wizard.reading', [
            'rule' => 'update on '.Post::class,
            'reach' => __('filament-bouncer::abilities.reach.record_reading', ['id' => '7']),
        ]));
});

test('the sentence reads the reach in words when no record is named', function (): void {
    signInAsAbilityManager();

    livewire(CreateAbility::class)
        ->fillForm([
            NarrowAbility::SUBJECT => Subject::keyFor(Post::class),
            NarrowAbility::ACTION => 'update',
            NarrowAbility::REACH => Reach::Owned->value,
        ])
        ->assertSee(__('filament-bouncer::abilities.wizard.reading', [
            'rule' => 'update on '.Post::class,
            'reach' => Reach::Owned->label(),
        ]));
});
