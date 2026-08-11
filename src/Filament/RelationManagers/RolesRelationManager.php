<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\RelationManagers;

use ElPandaPe\FilamentBouncer\Contracts\HoldsRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * The roles an account holds, on the account's own screen.
 *
 * Bouncer stores an assignment as a row of its own rather than a column, so the writes go
 * through its API and not through the relation: `assign()` and `retract()` are what fill
 * in the pivot the way the rest of the package expects, and both are followed by a
 * refresh, because nothing in Bouncer invalidates its cache on a write and one Livewire
 * request reads after it writes.
 */
class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament-bouncer::roles.relation.title');
    }

    /**
     * Asked of the resource rather than the Gate: the role model lives in a vendor
     * package, so Laravel's guessing never reaches a policy for it, and whoever may read
     * the roles screen is exactly who may read this tab.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return RoleResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('abilities_count')
                    ->label(__('filament-bouncer::roles.table.abilities'))
                    ->counts('abilities'),
            ])
            ->defaultSort('name')
            ->headerActions([
                Action::make('assign')
                    ->label(__('filament-bouncer::roles.relation.assign'))
                    ->modalHeading(__('filament-bouncer::roles.relation.assign'))
                    ->modalSubmitActionLabel(__('filament-bouncer::roles.relation.assign_submit'))
                    ->schema([
                        Select::make('role')
                            ->label(__('filament-bouncer::roles.relation.role'))
                            ->options(fn (): array => $this->offerable())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        /** @var string $name */
                        $name = $data['role'];

                        $this->owner()->assign($name);
                        Bouncer::refresh();
                    })
                    ->visible(fn (): bool => $this->offerable() !== []),
            ])
            ->recordActions([
                Action::make('retract')
                    ->label(__('filament-bouncer::roles.relation.retract'))
                    ->requiresConfirmation()
                    ->action(function (Model $record): void {
                        /** @var string $name */
                        $name = $record->getAttribute('name');

                        $this->owner()->retract($name);
                        Bouncer::refresh();
                    })
                    ->visible(fn (Model $record): bool => $this->mayRetract($record)),
            ])
            ->emptyStateHeading(__('filament-bouncer::roles.relation.empty'));
    }

    /**
     * Typed for the analyser and not at runtime: the contract writes down what Bouncer's
     * own trait already provides, and demanding that every application implement it would
     * be asking for a change none of them needs.
     *
     * @return Model&HoldsRoles
     */
    private function owner(): Model
    {
        /** @var Model&HoldsRoles $owner */
        $owner = $this->getOwnerRecord();

        return $owner;
    }

    /**
     * Every role the account does not hold yet, minus the privileged one for anybody who
     * does not hold it themselves. Nothing else is narrowed: being trusted with this
     * screen is the whole of the trust.
     *
     * @return array<string, string>
     */
    private function offerable(): array
    {
        $privileged = resolve(PrivilegedRole::class);
        /** @var array<int, string> $held */
        $held = $this->owner()->roles()->pluck('name')->all();

        /** @var array<string, string> $options */
        $options = Models::role()->newQuery()
            ->whereNotIn('name', $held)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->reject(static fn (string $name): bool => $privileged->isNamed($name)
                && ! $privileged->mayBeHandedOutBy(auth()->user()))
            ->all();

        return $options;
    }

    /**
     * The privileged role is kept on its last holder, and is only taken off anybody at
     * all by somebody who holds it: the way back in is not something a stranger closes.
     */
    private function mayRetract(Model $record): bool
    {
        /** @var string $name */
        $name = $record->getAttribute('name');

        $privileged = resolve(PrivilegedRole::class);

        if (! $privileged->isNamed($name)) {
            return true;
        }

        return $privileged->mayBeHandedOutBy(auth()->user())
            && ! $privileged->isLastHolder($this->getOwnerRecord());
    }
}
