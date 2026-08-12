<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\PresentsAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Support\AbilityFacts;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * A rule read as entries, under the sentence it adds up to.
 *
 * The definition is an infolist rather than the form drawn disabled, because nothing on
 * a record page is waiting to be typed into. Below it, the same holders card the
 * changing screen carries — read-only here — so whoever learns where a stance is on one
 * screen finds it in the same place on the other.
 *
 * The heading is the reconciliation's answer about this row, and it stands where a delete
 * button would have. That is the trade the screen makes: it will not offer to take a row
 * away, so it owes the reader an account of how the row does go — declared by the code and
 * safe, declared by nothing and due to be swept, or never the reconciliation's to speak
 * for at all.
 */
final class ViewAbility extends ViewRecord
{
    use PresentsAbility;

    protected static string $resource = AbilityResource::class;

    private ?AbilityFacts $facts = null;

    /**
     * The infolist takes the page over, so the parent no longer fills the form — and the
     * form is still there, drawing the holders below the entries.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->fillForm();
    }

    public function getSubheading(): string
    {
        return Declaration::of($this->getRecord())->note();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->phraseHero(),
            $this->getInfolistContentComponent(),
            $this->getFormContentComponent(),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([$this->definitionSection()]);
    }

    /**
     * The holders alone: the definition the resource's form carries beside them is
     * already on the entries above, and a record page that asked twice would answer
     * twice.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('filament-bouncer::abilities.form.holders'))
                ->description(__('filament-bouncer::abilities.form.holders_note'))
                ->schema([AbilityForm::holders()])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillFacts($data, $this->getRecord());
    }

    private function definitionSection(): Section
    {
        return Section::make(__('filament-bouncer::abilities.form.rule'))
            ->columns(3)
            ->schema([
                TextEntry::make('action')
                    ->label(__('filament-bouncer::abilities.wizard.action'))
                    ->state(fn (): HtmlString => new HtmlString(
                        e($this->recordFacts()->actionLabel)
                        .' <code class="fb-code">'.e($this->recordFacts()->actionName).'</code>',
                    )),
                TextEntry::make('entity_type')
                    ->label(__('filament-bouncer::abilities.form.entity'))
                    ->state(fn (): HtmlString => new HtmlString(
                        e($this->recordFacts()->subjectLabel)
                        .($this->recordFacts()->subjectClass === null
                            ? ''
                            : ' <code class="fb-code">'.e($this->recordFacts()->subjectClass).'</code>'),
                    )),
                TextEntry::make('reach')
                    ->label(__('filament-bouncer::abilities.form.reach'))
                    ->badge()
                    ->state(fn (): array => array_values(array_filter([
                        $this->recordFacts()->reachReading,
                        $this->recordFacts()->entityId === null ? null : 'entity_id = '.$this->recordFacts()->entityId,
                    ])))
                    ->color(fn (string $state): string => str_starts_with($state, 'entity_id')
                        ? 'gray'
                        : $this->recordFacts()->reachColor()),
                TextEntry::make('title')
                    ->label(__('filament-bouncer::abilities.form.title')),
                TextEntry::make('created_at')
                    ->label(__('filament-bouncer::abilities.form.created'))
                    ->since(),
            ]);
    }

    /**
     * Memoised because six entries read it and the catalogue behind it is not free.
     */
    private function recordFacts(): AbilityFacts
    {
        return $this->facts ??= AbilityFacts::of($this->getRecord());
    }
}
