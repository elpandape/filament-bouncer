<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use ElPandaPe\FilamentBouncer\Events\RoleAssignedEvent;
use ElPandaPe\FilamentBouncer\Filament\Concerns\HandsOutRoles;
use ElPandaPe\FilamentBouncer\Support\Causer;
use Filament\Forms\Components\CheckboxList;
use Filament\Support\Enums\Operation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * Handing an account its roles on the form that creates it.
 *
 * A relation manager needs a record to hang a row off and a creation screen has none, so the roles
 * are chosen here and written by `assign()` once there is something to assign them to.
 *
 * It shows on creation and nowhere else, because nowhere else can it act: `assign()` is called by
 * the creating page, so on an editing form the same ticks would be read, dropped and never
 * written — a control that cannot do what it offers. Editing an account's roles is the relation
 * manager's, which writes as it is used. A form that wants it anyway says so with `visibleOn()`.
 *
 * The privileged role is shown to everybody and ticked by only some: left off the list, somebody
 * would compose an account and never learn that the role holding everything exists.
 *
 * The lock is not the guard — `assign()` is, and it refuses both a name nobody may hand out and a
 * name no role has. The second is not caution: Bouncer's `assign` creates the role it cannot find,
 * so an unchecked name is not a failed write but a new empty role.
 */
final class RolesField extends CheckboxList
{
    use HandsOutRoles;

    /**
     * Where the choice lives in the form state.
     *
     * The creating page reads it from there by this name, because that state is the only
     * place it survives: the field is never dehydrated, so nothing of it reaches the
     * attributes the account is written from.
     */
    public const string NAME = 'roles';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-bouncer::roles.field.label'));
        $this->helperText(__('filament-bouncer::roles.field.note'));
        $this->options(static fn (): array => self::roles());
        $this->disableOptionWhen(static fn (string $value): bool => ! self::mayBeHandedOut($value));

        // Nothing named here is a column of the account, so a state that reached the
        // attributes would be handed to a mass assignment as a field that does not
        // exist — which under strict Eloquent throws rather than being ignored.
        $this->dehydrated(false);

        // Last, so a form that means to show it elsewhere overrides this with a call of its
        // own rather than having to fight the order of the two.
        $this->visibleOn(Operation::Create);
    }

    public static function getDefaultName(): string
    {
        return self::NAME;
    }

    /**
     * Writes the roles the account was given, refusing whatever the screen would not
     * have offered.
     *
     * Through Bouncer, since the relation's `attach` writes a pivot row Bouncer does not
     * recognise; and followed by a refresh, which nothing in Bouncer does on its own.
     *
     * @param  array<array-key, mixed>  $roles
     */
    public static function assign(Model $account, array $roles): void
    {
        $named = [];

        foreach ($roles as $role) {
            if (is_string($role) && self::isOffered($role)) {
                $named[] = $role;
            }
        }

        if ($named === []) {
            return;
        }

        Bouncer::assign(new Collection($named))->to($account);
        Bouncer::refresh();

        $causer = Causer::current();

        foreach ($named as $role) {
            event(new RoleAssignedEvent($account, $role, $causer));
        }
    }

    /**
     * Every role there is, called by whatever it is called by.
     *
     * @return array<string, string>
     */
    private static function roles(): array
    {
        $roles = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var string $name */
            $name = $role->getAttribute('name');

            /** @var string|null $title */
            $title = $role->getAttribute('title');

            $roles[$name] = blank($title) ? $name : $title;
        }

        return $roles;
    }

    private static function isOffered(string $name): bool
    {
        return array_key_exists($name, self::roles()) && self::mayBeHandedOut($name);
    }
}
