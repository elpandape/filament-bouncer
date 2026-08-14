<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Widgets;

use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesWidget;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Store\Ailment;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * How many rows of the abilities table are broken right now, and in what way.
 *
 * It answers what the listing cannot without being walked: the health column says whether **one**
 * row has something, and it takes every page to know whether the store as a whole is sound. And it
 * says it from outside the screen, which is where somebody finds out that something happened
 * without having gone to look.
 *
 * - **A count per ailment rather than one number**, because each is mended differently — a twin is
 *   decided, a ghost model is rewritten, an invisible row is uncovered — so "7 problems" does not
 *   say where to start.
 * - **Zero goes muted.** A zero in red is a permanent alarm, and an alarm that always sounds stops
 *   being read.
 * - **Every figure opens its rows**, with the listing already filtered by that ailment.
 *
 * **This widget is not registered by the plugin, and that is deliberate.** Registering it would put
 * it on every consumer's dashboard and — because the package refuses to boot a panel whose widgets
 * declare nobody — add an entity to their catalogue, turning `--check` red until they reconcile.
 * Whoever wants it says so; the README explains how.
 */
final class AbilityHealth extends StatsOverviewWidget
{
    use AuthorizesWidget;

    protected function getHeading(): string
    {
        return __('filament-bouncer::abilities.health.widget.heading');
    }

    protected function getDescription(): string
    {
        $census = resolve(Diagnosis::class)->census();

        return $census['ailing'] === 0
            ? __('filament-bouncer::abilities.health.widget.sound', ['total' => $census['total']])
            : __('filament-bouncer::abilities.health.widget.ailing', [
                'total' => $census['total'],
                'ailing' => $census['ailing'],
            ]);
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $counts = resolve(Diagnosis::class)->census()['counts'];

        return array_map(
            static fn (Ailment $ailment): Stat => Stat::make($ailment->getLabel(), (string) $counts[$ailment->value])
                ->icon($ailment->getIcon())
                ->color($counts[$ailment->value] === 0 ? 'gray' : $ailment->getColor())
                ->description(__($counts[$ailment->value] === 0
                    ? 'filament-bouncer::abilities.health.widget.nothing'
                    : 'filament-bouncer::abilities.health.widget.look'))
                ->url($counts[$ailment->value] === 0
                    ? null
                    : AbilityResource::getUrl('index', ['filters' => ['health' => ['value' => $ailment->value]]])),
            Ailment::all(),
        );
    }
}
