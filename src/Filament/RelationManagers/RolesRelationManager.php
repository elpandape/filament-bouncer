<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\RelationManagers;

use ElPandaPe\FilamentBouncer\Filament\Concerns\HandsOutRoles;
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
 * What one account may do, read and changed from the account's own screen.
 *
 * Two actions of this package's own where Filament's `AttachAction` and `DetachAction`
 * would have done, and the reason is not taste: both write straight onto the relation,
 * and the pivot row Bouncer keeps carries a scope column that an `attach` never fills in.
 * A role handed out that way is a row in the right table that answers no to everything.
 * Both actions here go through `Bouncer::assign()` and `Bouncer::retract()`, and both are
 * followed by a refresh — nothing in Bouncer clears its own cache, so without it the
 * table repaints from what was true before the click.
 *
 * The privileged role is the way back in when a mistake leaves nobody able to hand
 * anything out, and two things keep it that way from here. It is offered by nobody who does not
 * already hold it — unlike the creation form, which shows it locked, because a checklist
 * that quietly loses an entry teaches nothing while a pull-down of things to do next is
 * only made worse by an entry nobody may choose. And it is never taken off its last
 * holder, by anybody, since a way back in nobody holds is not one.
 *
 * Neither guard is decoration, and neither is asked twice. A pull-down refuses whatever
 * it never offered, and an action that is not visible is not mounted, so the refusal the
 * screen shows is the refusal a request typed by hand meets — asking again inside the
 * action would be a branch nothing could ever reach, which is a guard nobody can prove.
 * What holds them up instead is a pair of tests that arm both requests by hand: they are
 * the only thing standing between this comment and a screen that merely looks careful.
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

                        Bouncer::assign($name)->to($manager->getOwnerRecord());
                        Bouncer::refresh();
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

                        Bouncer::retract($name)->from($manager->getOwnerRecord());
                        Bouncer::refresh();
                    }),
            ])
            ->defaultSort('name')
            ->emptyStateHeading(__('filament-bouncer::roles.relation.empty'));
    }
}
