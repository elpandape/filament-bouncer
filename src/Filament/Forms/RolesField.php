<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use ElPandaPe\FilamentBouncer\Filament\Concerns\HandsOutRoles;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * Handing an account its roles on the form that creates it.
 *
 * A relation manager needs a record to hang a row off, and on a creation screen there is
 * none: the account does not exist until the form is submitted. So the roles are chosen
 * here and written afterwards, by `assign()`, once there is something to assign them to.
 *
 * The privileged role is shown to everybody and ticked by only some, which is the one
 * decision worth reading twice. Leaving it off the list entirely would be the tidier
 * screen and the worse one — somebody would compose an account, hand it what they could,
 * and never learn that the role holding everything exists at all. Shown and locked, the
 * screen says both things at once: here is the way back in, and it is not yours to give.
 *
 * Locking it is not the guard, though. The guard is `assign()`, the only thing here that
 * writes, and it hands a request nothing the screen would not have offered: neither a
 * name nobody may hand out, nor a name no role has. That second refusal is not caution
 * either — Bouncer's `assign` creates the role it cannot find, so a name let through
 * unchecked is not a failed write but a new empty role, silently.
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
    }

    public static function getDefaultName(): string
    {
        return self::NAME;
    }

    /**
     * Writes the roles the account was given, refusing whatever the screen would not
     * have offered.
     *
     * Through Bouncer rather than through the relation, because the relation's own
     * `attach` writes a pivot row Bouncer does not recognise. And followed by a refresh,
     * which nothing in Bouncer does on its own: without it the very next question about
     * this account in this request is answered out of the cache from before the write.
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
