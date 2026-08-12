<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\NarrowAbility;
use ElPandaPe\FilamentBouncer\Store\Reach;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * Narrowing a rule, one question at a time.
 *
 * Three steps, because the sentence they add up to is not obvious from any one of them.
 * "Change, on posts, for whatever its holder owns" is a rule somebody can weigh; three
 * pull-downs on one page are three answers with the question left implicit, and the last
 * step exists so the sentence is read once before it is written.
 *
 * The sentence itself stands above the wizard the whole way, recomposing as the choices
 * are made: the empty chips say what is still to be chosen, and a filled one says what
 * the choice meant, in words rather than in a method name.
 */
final class CreateAbility extends CreateRecord
{
    use HasWizard {
        getWizardComponent as makeWizardComponent;
    }

    protected static string $resource = AbilityResource::class;

    public function getSubheading(): string
    {
        return __('filament-bouncer::abilities.wizard.subtitle');
    }

    /**
     * The class is what lets the package's stylesheet reorder the footer the way the
     * approved design has it — moving is left to the CSS, walking is left to Filament.
     */
    public function getWizardComponent(): Component
    {
        return $this->makeWizardComponent()->extraAttributes(['class' => 'fb-wizard']);
    }

    /**
     * The parent's content with the live sentence mounted above the wizard.
     */
    public function content(Schema $schema): Schema
    {
        // The analyser works out `view-string` by looking for the file among the paths
        // the application renders from, and a package's namespaced view is never among
        // them.
        /** @var view-string $hero */
        $hero = 'filament-bouncer::forms.phrase-hero';

        return $schema->components([
            View::make($hero)->viewData(fn (): array => [
                'live' => true,
                'paths' => [
                    'subject' => 'data.'.NarrowAbility::SUBJECT,
                    'action' => 'data.'.NarrowAbility::ACTION,
                    'reach' => 'data.'.NarrowAbility::REACH,
                    'record' => 'data.'.NarrowAbility::RECORD,
                ],
                'maps' => $this->phraseMaps(),
            ]),
            $this->getFormContentComponent(),
        ]);
    }

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

    /**
     * The words the live sentence composes with, handed to the client whole: every
     * subject's label, every action's label under its subject, and the reaches — so the
     * chips recompose on the spot instead of waiting a round trip for a translation.
     *
     * @return array{subjects: array<string, string>, actions: array<string, array<string, string>>, reaches: array<string, string>, recordValue: string, recordReading: string}
     */
    private function phraseMaps(): array
    {
        $subjects = [];
        $actions = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $key => $subject) {
            $subjects[$key] = $subject->label;
            $actions[$key] = NarrowAbility::actions($key);
        }

        $reaches = [];

        foreach (Reach::cases() as $reach) {
            $reaches[$reach->value] = $reach->label();
        }

        return [
            'subjects' => $subjects,
            'actions' => $actions,
            'reaches' => $reaches,
            'recordValue' => Reach::Record->value,
            'recordReading' => __('filament-bouncer::abilities.reach.record_reading'),
        ];
    }
}
