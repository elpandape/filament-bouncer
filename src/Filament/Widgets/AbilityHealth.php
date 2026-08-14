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
 * A count per ailment and not one number, because each is mended differently, so "7 problems" does
 * not say where to start. Zero goes muted: an alarm that always sounds stops being read.
 *
 * **The plugin does not register this widget.** Doing so would put it on every consumer's dashboard
 * and — since a panel refuses to boot with a widget that declares nobody — add an entity to their
 * catalogue, turning `--check` red until they reconcile. The README explains how to add it.
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
