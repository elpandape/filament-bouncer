<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\NarrowAbility;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * Narrowing a rule, one question at a time.
 *
 * Three steps, because the sentence they add up to is not obvious from any one of them.
 * "Change, on posts, for whatever its holder owns" is a rule somebody can weigh; three
 * pull-downs on one page are three answers with the question left implicit, and the last
 * step exists so the sentence is read once before it is written.
 */
final class CreateAbility extends CreateRecord
{
    use HasWizard;

    protected static string $resource = AbilityResource::class;

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return [
            Step::make(__('filament-bouncer::abilities.wizard.ability'))
                ->description(__('filament-bouncer::abilities.wizard.ability_hint'))
                ->schema(NarrowAbility::ability())
                ->columns(2),
            Step::make(__('filament-bouncer::abilities.wizard.reach'))
                ->description(__('filament-bouncer::abilities.wizard.reach_hint'))
                ->schema(NarrowAbility::reach()),
            Step::make(__('filament-bouncer::abilities.wizard.review'))
                ->description(__('filament-bouncer::abilities.wizard.review_hint'))
                ->schema([
                    Text::make(NarrowAbility::review()),
                    NarrowAbility::title(),
                ]),
        ];
    }

    /**
     * Written by hand rather than through a mass assignment, because the three columns
     * that decide how far a rule reaches are not fillable on Bouncer's own model — and
     * under strict Eloquent an assignment that names them throws rather than dropping
     * them quietly.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $ability = Models::ability();

        // The pair has already met the rule that refuses whatever the catalogue cannot
        // resolve, so the fallback is out of reach. It is written as an expression rather
        // than a branch precisely because a branch nothing can reach is a branch no test
        // can cover, and an uncoverable branch is how a screen grows code nobody checks.
        $ability->forceFill(NarrowAbility::attributes($data) ?? $data)->save();

        return $ability;
    }
}
