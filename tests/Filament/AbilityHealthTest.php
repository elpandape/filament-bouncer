<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesWidget;
use ElPandaPe\FilamentBouncer\Filament\FilamentBouncerPlugin;
use ElPandaPe\FilamentBouncer\Filament\Widgets\AbilityHealth;
use ElPandaPe\FilamentBouncer\Store\Ailment;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    signInAsAbilityManager();
});

/**
 * @param  array<string, mixed>  $columns
 */
function ailing(array $columns = []): Model
{
    $ability = Models::ability();

    $ability->forceFill([...['name' => 'archive', 'only_owned' => false], ...$columns])->save();

    return $ability;
}

test('says nothing is wrong when nothing is wrong', function (): void {
    livewire(AbilityHealth::class)
        ->assertSee(__('filament-bouncer::abilities.health.widget.nothing'))
        ->assertOk();
});

test('counts each ailment apart, because each one is mended differently', function (): void {
    ailing(['entity_type' => Post::class]);
    ailing(['entity_type' => Post::class]);
    ailing(['name' => 'export', 'scope' => 7]);

    livewire(AbilityHealth::class)
        ->assertSee(Ailment::Twin->getLabel())
        ->assertSee(Ailment::Invisible->getLabel())
        ->assertSee(__('filament-bouncer::abilities.health.widget.look'))
        ->assertOk();
});

test('leads from a figure to the rows it counted', function (): void {
    ailing(['scope' => 7]);

    livewire(AbilityHealth::class)
        ->assertSeeHtml(e(ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource::getUrl('index', [
            'filters' => ['health' => ['value' => Ailment::Invisible->value]],
        ])))
        ->assertOk();
});

/**
 * The guard refuses to boot a panel whose widgets declare nobody, so a widget that ships without
 * this would take down the panel of whoever added it.
 */
test('declares who may see it, which the panel guard requires', function (): void {
    expect(class_uses_recursive(AbilityHealth::class))->toContain(AuthorizesWidget::class);
});

/**
 * Registering it would put it on every consumer's dashboard and add a subject to their catalogue,
 * turning `--check` red until they reconcile. Whoever wants it says so.
 */
test('is not registered by the plugin, so nobody gets it without asking', function (): void {
    // Asked of a bare panel the plugin has just registered itself on, which is exactly what a
    // consumer's panel does and nothing more.
    $panel = Panel::make()->id('probe');

    FilamentBouncerPlugin::make()->register($panel);

    expect($panel->getWidgets())->toBeEmpty();
});
