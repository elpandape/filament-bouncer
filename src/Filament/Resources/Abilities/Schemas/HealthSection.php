<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

/**
 * The questions this screen asks about a row, with their answers, always.
 *
 * At the foot and under the restrictions, so it reads as the closing note rather than as an alarm.
 * Asked always, and not only where something failed: the question says what is being looked at, so
 * a sound row shows four ticks with their fact instead of a blank that teaches nobody what was
 * checked. The price is four lines on every record page.
 *
 * Each answer carries its fact: "duplicate" without saying which sends the reader off to find it.
 */
final class HealthSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-bouncer::abilities.health.heading'))
            ->description(__('filament-bouncer::abilities.health.note'))
            ->icon('heroicon-o-heart')
            ->collapsible()
            ->columns(2)
            ->schema(fn (Model $record): array => array_map(
                static fn (array $check): TextEntry => TextEntry::make('check-'.$check['ailment']->value)
                    ->label($check['ailment']->question())
                    ->state($check['reading'])
                    // A good answer's tick goes muted rather than green: four green ticks on every
                    // record page compete with the one that occasionally has something to say.
                    ->icon($check['failed'] ? $check['ailment']->getIcon() : 'heroicon-m-check')
                    ->color($check['failed'] ? $check['ailment']->getColor() : 'gray'),
                resolve(Diagnosis::class)->checks($record),
            ));
    }
}
