<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\RelationManagers;

use BackedEnum;
use ElPandaPe\FilamentBouncer\Events\RoleAssignedEvent;
use ElPandaPe\FilamentBouncer\Events\RoleRetractedEvent;
use ElPandaPe\FilamentBouncer\Filament\Concerns\HandsOutRoles;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Support\Causer;
use ElPandaPe\FilamentBouncer\Support\RoleHolding;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * What one account may do, read and changed from the account's own screen.
 *
 * Actions of this package's own rather than Filament's `AttachAction` and `DetachAction`: both
 * write straight onto the relation, and Bouncer's pivot carries a scope column an `attach` never
 * fills in, leaving a row in the right table that answers no to everything. Every write goes
 * through Bouncer and is followed by a refresh, since nothing in Bouncer clears its own cache.
 *
 * The privileged role is the way back in: it is offered by nobody who does not already hold it,
 * and never taken off its last holder. Neither guard is asked twice — a pull-down refuses what it
 * never offered and an invisible action is never mounted — so what holds them up is the pair of
 * tests that arm both requests by hand.
 */
final class RolesRelationManager extends RelationManager
{
    use HandsOutRoles;

    protected static string $relationship = 'roles';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament-bouncer::roles.relation.title');
    }

    /**
     * None unless the application names one: the icon family is its decision, like every
     * other icon this package draws.
     *
     * It reaches the screen only where Filament builds a tab for the manager, which it does
     * from the second one on; a resource carrying this one alone draws the table plain.
     */
    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|Htmlable|null
    {
        /** @var string|BackedEnum|Htmlable|null $icon */
        $icon = config('filament-bouncer.relation.icon');

        return $icon;
    }

    /**
     * The roles this account may be handed, by whoever is at the keyboard.
     *
     * @return array<string, string>
     */
    public function assignable(): array
    {
        $roles = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var string $name */
            $name = $role->getAttribute('name');

            if (! self::mayBeHandedOut($name)) {
                continue;
            }

            /** @var string|null $title */
            $title = $role->getAttribute('title');

            $roles[$name] = blank($title) ? $name : $title;
        }

        return $roles;
    }

    /**
     * Whether this role may be taken off this account.
     *
     * The only answer that is ever no is the privileged role on the one account left
     * holding it. Every other role, privileged or not, comes off: it is the emptiness
     * that cannot be undone, not the taking.
     */
    public function mayBeTakenAway(Model $role): bool
    {
        $privileged = app(PrivilegedRole::class);

        /** @var string $name */
        $name = $role->getAttribute('name');

        if (! $privileged->isNamed($name)) {
            return true;
        }

        return ! $privileged->isLastHolder($this->getOwnerRecord());
    }

    public function table(Table $table): Table
    {
        $manager = $this;

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-bouncer::roles.relation.role'))
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->description(static function (Model $record): ?string {
                        /** @var string|null $title */
                        $title = $record->getAttribute('title');

                        return $title;
                    }),
            ])
            ->headerActions([
                Action::make('assign')
                    ->label(__('filament-bouncer::roles.relation.assign'))
                    ->modalSubmitActionLabel(__('filament-bouncer::roles.relation.assign_submit'))
                    ->schema([
                        Select::make('role')
                            ->label(__('filament-bouncer::roles.relation.role'))
                            ->options(static fn (): array => $manager->assignable())
                            ->required(),
                    ])
                    ->action(static function (array $data) use ($manager): void {
                        /** @var string $name */
                        $name = $data['role'];

                        $account = $manager->getOwnerRecord();

                        // Asked before the write: assign() is idempotent, and afterwards the
                        // pivot row exists either way, leaving nothing to tell whether it was
                        // already there. The pull-down still offers a role already held —
                        // that stays a harmless no-op write, only the announcement is guarded.
                        $alreadyHeld = RoleHolding::of($account, $name);

                        Bouncer::assign($name)->to($account);
                        Bouncer::refresh();

                        if (! $alreadyHeld) {
                            event(new RoleAssignedEvent($account, $name, Causer::current()));
                        }
                    }),
            ])
            ->recordActions([
                // The row says the role's name and its title, and nothing about what it may do.
                // That lives one screen away, and without a way through, reading it means going
                // to the roles listing and finding the row again by hand.
                Action::make('view')
                    ->label(__('filament-bouncer::roles.relation.view'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(static fn (Model $record): string => RoleResource::getUrl('view', ['record' => $record]))
                    ->visible(static fn (Model $record): bool => RoleResource::canView($record)),
                Action::make('retract')
                    ->label(__('filament-bouncer::roles.relation.retract'))
                    ->requiresConfirmation()
                    ->visible(static fn (Model $record): bool => $manager->mayBeTakenAway($record))
                    ->action(static function (Model $record) use ($manager): void {
                        /** @var string $name */
                        $name = $record->getAttribute('name');

                        $account = $manager->getOwnerRecord();

                        Bouncer::retract($name)->from($account);
                        Bouncer::refresh();

                        event(new RoleRetractedEvent($account, $name, Causer::current()));
                    }),
            ])
            ->defaultSort('name')
            ->emptyStateHeading(__('filament-bouncer::roles.relation.empty'));
    }
}
