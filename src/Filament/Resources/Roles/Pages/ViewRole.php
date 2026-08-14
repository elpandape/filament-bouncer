<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Infolists\AbilityTags;
use ElPandaPe\FilamentBouncer\Filament\Infolists\OrphanChips;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What a role says, read in the shape it is written in.
 *
 * The same grid as the edit screen, disabled: chips or a list of names would make reading and
 * changing two different pictures of the same thing.
 *
 * The denials get a card even when there are none, since a denial beats any grant arriving through
 * another role and knowing there is none is worth saying.
 *
 * Taking the role off a holder is the one write here, and it refuses the last holder of the way
 * back in — in the writing, not only in the drawing.
 */
final class ViewRole extends ViewRecord
{
    use FillsRoleAbilities;

    protected static string $resource = RoleResource::class;

    /**
     * The infolist takes the page over, so the parent no longer fills the form — and the
     * form is still there, drawing the grid below the entries.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->fillForm();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getInfolistContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    /**
     * Wide on the left, narrow on the right.
     *
     * What the role says takes the width, because it is what the page is opened for. The
     * warning about what it is losing goes in the narrow column beside the metadata: a
     * warning does not need the width of the content, it needs to be where people look.
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 3])
                    ->schema([
                        $this->identitySection(),
                        $this->abilitiesSection(),
                    ]),
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        $this->tenantSection(),
                        $this->orphansSection(),
                        $this->metadataSection(),
                    ]),
            ]);
    }

    /**
     * The way in, offered only where there is one.
     *
     * Asked of the resource and not the policy: a record page offering a door onto a page that
     * aborts is worse than one offering none.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn (): string => RoleResource::getUrl('edit', ['record' => $this->getRecord()]))
                ->visible(fn (): bool => RoleResource::canEdit($this->getRecord())),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillStances($data, $this->getRecord());
    }

    /**
     * What the role says, and what it is about to lose.
     *
     * This used to be the grid over again, disabled. A grid nobody can touch has to draw
     * every cell to answer three things, and the three that matter read as faintly as the
     * ones saying nothing — so the reading is tags, and the grid stays where it is settable.
     */
    private function abilitiesSection(): Section
    {
        return Section::make(__('filament-bouncer::roles.record.abilities_heading'))
            ->description(__('filament-bouncer::roles.record.abilities_note'))
            ->icon('heroicon-o-key')
            ->schema([AbilityTags::make('abilities')->hiddenLabel()])
            ->columnSpanFull();
    }

    private function orphansSection(): Section
    {
        return Section::make(__('filament-bouncer::roles.record.orphans_heading'))
            ->icon('heroicon-o-link-slash')
            ->schema([OrphanChips::make('orphans')->hiddenLabel()]);
    }

    /**
     * When and what tenant, which is what is known about the role rather than what it does.
     */
    private function metadataSection(): Section
    {
        return Section::make(__('filament-bouncer::roles.record.metadata'))
            ->schema([
                TextEntry::make('created_at')
                    ->label(__('filament-bouncer::roles.record.created'))
                    ->isoDate('lll'),
                TextEntry::make('updated_at')
                    ->label(__('filament-bouncer::roles.record.updated'))
                    ->isoDate('lll'),
            ]);
    }

    private function identitySection(): Section
    {
        return Section::make(__('filament-bouncer::roles.record.identity'))
            ->description(__('filament-bouncer::roles.record.identity_note'))
            ->icon('heroicon-o-shield-check')
            ->schema([
                TextEntry::make('name')
                    ->label(__('filament-bouncer::roles.record.name'))
                    ->badge()
                    ->copyable(),
                TextEntry::make('title')
                    ->label(__('filament-bouncer::roles.record.title'))
                    ->placeholder(__('filament-bouncer::roles.record.title_empty')),
            ]);
    }

    /**
     * The tenant is drawn only where the installation scopes its rows. Where it does not, every
     * role carries the same nothing, and a section saying so on every record page is a heading
     * that never varies.
     */
    private function tenantSection(): Section
    {
        return Section::make(__('filament-bouncer::roles.record.scope'))
            ->visible(fn (): bool => resolve(Tenancy::class)->inUse())
            ->schema([
                TextEntry::make('scope')
                    ->hiddenLabel()
                    ->numeric()
                    ->placeholder(__('filament-bouncer::roles.record.scope_global')),
            ]);
    }
}
