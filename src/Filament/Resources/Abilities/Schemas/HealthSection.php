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
 * It sits at the foot, under the restrictions: read last it stops being an alarm that interrupts
 * and becomes the closing note — "and this row, is it sound?".
 *
 * It was chosen against the alternative, which showed only what failed and carried the button that
 * mends it. What decided it is that **this one explains itself**: the question already says what is
 * being looked at, so no paragraph per ailment is needed, and a sound row stops being a blank —
 * four ticks with their fact rather than a "nothing wrong" that teaches nobody what was checked.
 * The price, known: four lines on every record page even when nothing is the matter.
 *
 * The answer carries the fact inside, and that is what makes it useful: "duplicate" without saying
 * **which** sends the reader off to find the other row, and "ghost model" without saying **which
 * class** hides the first thing needed to mend it.
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
