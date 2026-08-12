<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Concerns\FillsRoleAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas\RoleForm;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\RoleCoverage;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Initials;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * What a role says, read in the shape it is written in.
 *
 * The identity is entries rather than disabled inputs, because nothing on a record page
 * is waiting to be typed into. Below them the same grid as the edit screen, disabled:
 * chips or a list of names would have made reading and changing two different pictures
 * of the same thing, and somebody who learns where a cell is here would then have to
 * find it again over there.
 *
 * The denials get a card of their own even when there are none, because a denial beats
 * any grant arriving through another role: knowing there is none is worth a card.
 *
 * And the people holding the role are on the page too, with the one way this screen may
 * change anything: taking the role off one of them. That write goes through Bouncer and
 * refuses the last holder of the way back in — in the writing, not only in the drawing.
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

    public function getSubheading(): ?string
    {
        /** @var string|null $title */
        $title = $this->getRecord()->getAttribute('title');

        return $title;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getInfolistContentComponent(),
            $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            $this->identitySection(),
            $this->forbiddenSection(),
            $this->holdersSection(),
        ]);
    }

    /**
     * The grid alone: the identity the resource's form carries beside it is already on
     * the entries above, and a record page that asked twice would answer twice.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([RoleForm::grid()])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Taking the role off one of its holders, armed from the holders card.
     *
     * The write goes through Bouncer rather than the relation, because the pivot row
     * Bouncer keeps carries a scope column an `attach` never fills in; and it refuses
     * the last holder of the way back in even when a request arms it by hand — the
     * hidden button alone would be theatre.
     */
    public function retractRoleAction(): Action
    {
        return Action::make('retractRole')
            ->label(__('filament-bouncer::roles.record.retract'))
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                $holder = Models::user()->newQuery()->find($arguments['holder'] ?? null);

                if (! $holder instanceof Model) {
                    return;
                }

                /** @var string $name */
                $name = $this->getRecord()->getAttribute('name');

                $privileged = app(PrivilegedRole::class);

                if ($privileged->isNamed($name) && $privileged->isLastHolder($holder)) {
                    return;
                }

                Bouncer::retract($name)->from($holder);
                Bouncer::refresh();
            });
    }

    /**
     * The way in, offered only where there is one.
     *
     * The resource refuses to edit your own role and the way back in, and this asks it
     * rather than the policy: a record page that offered a door onto a page which aborts
     * would be worse than one that offers none.
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

    private function identitySection(): Section
    {
        // The analyser works out `view-string` by looking for the file among the paths
        // the application renders from, and a package's namespaced view is never among
        // them.
        /** @var view-string $coverage */
        $coverage = 'filament-bouncer::roles.coverage';

        return Section::make(__('filament-bouncer::roles.record.identity'))
            ->icon('heroicon-o-shield-check')
            ->columns(3)
            ->schema([
                TextEntry::make('name')
                    ->label(__('filament-bouncer::roles.record.name'))
                    ->badge()
                    ->color('warning'),
                TextEntry::make('title')
                    ->label(__('filament-bouncer::roles.record.title')),
                TextEntry::make('holders')
                    ->label(__('filament-bouncer::roles.record.holders'))
                    ->state(fn (): int => $this->holdersCount()),
                TextEntry::make('updated_at')
                    ->label(__('filament-bouncer::roles.record.updated'))
                    ->since(),
                TextEntry::make('created_at')
                    ->label(__('filament-bouncer::roles.record.created'))
                    ->since(),
                View::make($coverage)
                    ->viewData(fn (): array => [
                        'coverage' => RoleCoverage::for($this->getRecord(), app(CatalogRegistry::class)->current()),
                        'detailed' => true,
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function forbiddenSection(): Section
    {
        /** @var view-string $card */
        $card = 'filament-bouncer::roles.forbidden-card';

        return Section::make(__('filament-bouncer::roles.record.forbidden_heading'))
            ->schema([
                View::make($card)->viewData(fn (): array => ['forbidden' => $this->forbidden()]),
            ]);
    }

    private function holdersSection(): Section
    {
        /** @var view-string $card */
        $card = 'filament-bouncer::roles.holders';

        return Section::make(__('filament-bouncer::roles.record.holders_heading'))
            ->schema([
                View::make($card)->viewData(fn (): array => ['holders' => $this->holders()]),
            ]);
    }

    private function holdersCount(): int
    {
        return DB::table(Models::table('assigned_roles'))
            ->where('role_id', $this->getRecord()->getKey())
            ->count();
    }

    /**
     * The denials this role holds, said in the words of the catalogue.
     *
     * @return array<int, array{action: string, subject: string}>
     */
    private function forbidden(): array
    {
        $state = app(RoleAbilities::class)->toFormState($this->getRecord());
        $labels = app(Labels::class);
        $rows = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $key => $subject) {
            foreach (array_keys($subject->cells()) as $action) {
                if (($state[$key][$action] ?? '') !== Stance::Forbidden->value) {
                    continue;
                }

                $rows[] = [
                    'action' => $action === Ability::MANAGE_ACTION
                        ? __('filament-bouncer::roles.form.manage')
                        : $labels->action($action),
                    'subject' => $subject->label,
                ];
            }
        }

        return $rows;
    }

    /**
     * The accounts holding this role, read through the pivot because the account model
     * is whatever the application configured. The attributes are taken off the raw
     * array rather than the accessors, so an account model without a name or an email
     * answers null instead of throwing under `Model::shouldBeStrict()`.
     *
     * @return array<int, array{key: int|string, name: string, email: string|null, initials: string, removable: bool}>
     */
    private function holders(): array
    {
        $accounts = Models::user();
        $assigned = Models::table('assigned_roles');

        $records = $accounts->newQuery()
            ->join($assigned, $assigned.'.entity_id', '=', $accounts->getQualifiedKeyName())
            ->where($assigned.'.entity_type', $accounts->getMorphClass())
            ->where($assigned.'.role_id', $this->getRecord()->getKey())
            ->get([$accounts->getTable().'.*']);

        /** @var string $roleName */
        $roleName = $this->getRecord()->getAttribute('name');

        $privileged = app(PrivilegedRole::class);
        $guarded = $privileged->isNamed($roleName);
        $holders = [];

        foreach ($records as $record) {
            $attributes = $record->getAttributes();

            $name = $attributes['name'] ?? null;
            $email = $attributes['email'] ?? null;

            /** @var int|string $key */
            $key = $record->getKey();

            $shown = is_string($name) && $name !== '' ? $name : (is_string($email) ? $email : (string) $key);

            $holders[] = [
                'key' => $key,
                'name' => $shown,
                'email' => is_string($email) ? $email : null,
                'initials' => Initials::of($shown),
                'removable' => ! $guarded || ! $privileged->isLastHolder($record),
            ];
        }

        return $holders;
    }
}
