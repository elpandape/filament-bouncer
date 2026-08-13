<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Store\Ailment;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

/**
 * @param  array<string, mixed>  $columns
 */
function rule(array $columns = []): Model
{
    $ability = Models::ability();

    $ability->forceFill([...['name' => 'archive', 'only_owned' => false], ...$columns])->save();

    return $ability;
}

function diagnosis(): Diagnosis
{
    return resolve(Diagnosis::class);
}

/**
 * The four answers keyed by ailment, declared wide on purpose: the tenant check is not always among
 * them, and an exact shape would have every read of it look like a mistake.
 *
 * @return array<string, string>
 */
function readings(Model $ability): array
{
    $out = [];

    foreach (diagnosis()->checks($ability) as $check) {
        $out[$check['ailment']->value] = $check['reading'];
    }

    return $out;
}

test('finds nothing wrong with an ordinary rule', function (): void {
    $rule = rule(['entity_type' => Post::class]);

    expect(diagnosis()->of($rule))->toBeEmpty()
        ->and(diagnosis()->isHealthy($rule))->toBeTrue()
        ->and(diagnosis()->severity($rule))->toBe(Diagnosis::HEALTHY);
});

test('marks both rows when two say exactly the same thing', function (): void {
    $one = rule(['entity_type' => Post::class]);
    $other = rule(['entity_type' => Post::class]);

    expect(diagnosis()->of($one))->toBe([Ailment::Twin])
        ->and(diagnosis()->of($other))->toBe([Ailment::Twin])
        ->and(diagnosis()->severity($one))->toBe(Diagnosis::SEVERE);
});

test('does not call twins two rows differing in the column that fences them', function (): void {
    $one = rule(['entity_type' => Post::class]);
    rule(['entity_type' => Post::class, 'only_owned' => true]);

    expect(diagnosis()->of($one))->toBeEmpty();
});

test('does not call twins two rows belonging to different tenants', function (): void {
    $global = rule();
    rule(['scope' => 7]);

    expect(diagnosis()->of($global))->toBeEmpty();
});

test('finds a twin among rows a tenant hides, which nothing else would see', function (): void {
    $hidden = rule(['scope' => 7]);
    rule(['scope' => 7]);

    expect(diagnosis()->of($hidden))->toBe([Ailment::Twin, Ailment::Invisible]);
});

test('marks a rule speaking of a model that can no longer be loaded', function (): void {
    expect(diagnosis()->of(rule(['entity_type' => 'App\\Models\\Gone'])))
        ->toBe([Ailment::GhostModel]);
});

test('marks a rule fenced to a record that is already gone', function (): void {
    $post = Post::forceCreate(['title' => 'Fleeting']);
    $id = $post->getKey();
    $ghost = rule(['entity_type' => Post::class, 'entity_id' => $id]);

    $post->forceDelete();

    expect(diagnosis()->of($ghost))->toBe([Ailment::GhostRecord]);
});

test('says only that the model is gone when the record cannot even be asked about', function (): void {
    expect(diagnosis()->of(rule(['entity_type' => 'App\\Models\\Gone', 'entity_id' => 3])))
        ->toBe([Ailment::GhostModel]);
});

test('leaves a rule reaching every model alone', function (): void {
    expect(diagnosis()->of(rule(['entity_type' => '*'])))->toBeEmpty();
});

test('marks a rule the rest of the system cannot see, and calls it out of sight rather than broken', function (): void {
    $hidden = rule(['scope' => 7]);

    expect(diagnosis()->of($hidden))->toBe([Ailment::Invisible])
        ->and(diagnosis()->severity($hidden))->toBe(Diagnosis::HIDDEN);
});

test('reports every ailment a single row suffers at once', function (): void {
    rule(['entity_type' => 'App\\Models\\Gone', 'scope' => 7]);

    expect(diagnosis()->of(rule(['entity_type' => 'App\\Models\\Gone', 'scope' => 7])))
        ->toBe([Ailment::Twin, Ailment::GhostModel, Ailment::Invisible]);
});

test('answers each of the four questions with the fact inside', function (): void {
    $post = Post::forceCreate(['title' => 'Kept']);
    $twin = rule(['entity_type' => Post::class, 'entity_id' => $post->getKey()]);
    $rule = rule(['entity_type' => Post::class, 'entity_id' => $post->getKey()]);

    $readings = readings($rule);

    expect($readings[Ailment::Twin->value])->toContain(keyOf($twin))
        ->and($readings[Ailment::GhostModel->value])->toContain(Post::class)
        ->and($readings[Ailment::GhostRecord->value])->toContain(keyOf($post))
        ->and($readings[Ailment::Invisible->value])->not->toBeEmpty();
});

test('answers about the model whatever shape the rule names it in', function (): void {
    $loose = readings(rule());
    $wildcard = readings(rule(['entity_type' => '*']));
    $gone = readings(rule(['entity_type' => 'App\\Models\\Gone', 'entity_id' => 3]));

    expect($loose[Ailment::GhostModel->value])->toBe(__('filament-bouncer::abilities.health.ghost-model.none'))
        ->and($loose[Ailment::GhostRecord->value])->toBe(__('filament-bouncer::abilities.health.ghost-record.none'))
        ->and($wildcard[Ailment::GhostModel->value])->toBe(__('filament-bouncer::abilities.health.ghost-model.any'))
        ->and($gone[Ailment::GhostRecord->value])->toBe(__('filament-bouncer::abilities.health.ghost-record.unknown'));
});

test('counts every ailment in one pass, and counts the sound rows apart', function (): void {
    rule(['entity_type' => Post::class]);
    rule(['entity_type' => Post::class]);
    rule(['name' => 'export', 'scope' => 7]);
    rule(['name' => 'publish', 'entity_type' => Post::class]);

    $census = diagnosis()->census();

    expect($census['total'])->toBe(4)
        ->and($census['ailing'])->toBe(3)
        ->and($census['counts'][Ailment::Twin->value])->toBe(2)
        ->and($census['counts'][Ailment::Invisible->value])->toBe(1)
        ->and($census['counts'][Ailment::GhostModel->value])->toBe(0)
        // Asked twice, counted once: the second answer is the remembered one.
        ->and(diagnosis()->census())->toBe($census);
});

test('hands back the rows suffering an ailment, for the filter', function (): void {
    $hidden = rule(['scope' => 7]);
    rule(['entity_type' => Post::class]);

    expect(diagnosis()->keysWith(Ailment::Invisible))->toBe([$hidden->getKey()]);
});

test('forgets what it looked up when a screen writes', function (): void {
    $alone = rule(['entity_type' => Post::class]);
    $diagnosis = diagnosis();

    expect($diagnosis->of($alone))->toBeEmpty();

    rule(['entity_type' => Post::class]);

    expect($diagnosis->of($alone))->toBeEmpty();

    $diagnosis->forget();

    expect($diagnosis->of($alone))->toBe([Ailment::Twin]);
});

test('every ailment says what it is, how loud it is and what to do about it', function (): void {
    foreach (Ailment::all() as $ailment) {
        expect($ailment->getLabel())->not->toBeEmpty()
            ->and($ailment->question())->not->toBeEmpty()
            ->and($ailment->note())->not->toBeEmpty()
            ->and($ailment->getIcon())->toStartWith('heroicon-')
            ->and($ailment->getColor())->toBe($ailment === Ailment::Invisible ? 'warning' : 'danger')
            ->and($ailment->isSevere())->toBe($ailment !== Ailment::Invisible);
    }
});

test('says nothing about the tenant where the installation uses one', function (): void {
    config()->set('filament-bouncer.tenancy', true);

    $hidden = rule(['scope' => 7]);

    // Read through the tenant scope, a row of another tenant is not even in reach, so what is
    // asserted is the one thing that stays true: it is not called an anomaly.
    expect(diagnosis()->of($hidden))->not->toContain(Ailment::Invisible)
        ->and(array_column(diagnosis()->checks($hidden), 'ailment'))->not->toContain(Ailment::Invisible);
});
