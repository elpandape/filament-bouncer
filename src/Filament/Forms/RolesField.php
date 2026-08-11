<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use ElPandaPe\FilamentBouncer\Contracts\HoldsRoles;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * The roles handed to an account as it is created, where a relation manager cannot reach:
 * that one needs a record to hang off, and on the create screen there is none yet.
 *
 * Three things here are not decoration. The privileged role is offered disabled rather
 * than hidden to whoever may not hand it on, so its holders are legible from the screen
 * instead of being a gap; the rule that validates the field is widened to accept it, or a
 * disabled box that is already ticked would refuse to save; and the write is taken over
 * here rather than left to Eloquent, so a request that names the role by hand is refused
 * the same way the screen refuses it.
 */
final class RolesField
{
    public const string NAME = 'filament_bouncer_roles';

    public static function make(): CheckboxList
    {
        return CheckboxList::make(self::NAME)
            ->label(__('filament-bouncer::roles.field.label'))
            ->helperText(__('filament-bouncer::roles.field.note'))
            ->options(static fn (): array => self::options())
            ->disableOptionWhen(static fn (string $value): bool => ! self::mayHandOut($value))
            ->dehydrated(false)
            ->rule(static fn (): string => 'array')
            ->bulkToggleable();
    }

    /**
     * Writes what the screen would have offered, and nothing else. Called from the page
     * once the record exists, because an account cannot hold a role before it is one.
     *
     * @param  array<int, string>  $names
     */
    public static function assign(Model $record, array $names): void
    {
        /** @var Model&HoldsRoles $holder */
        $holder = $record;

        $allowed = array_filter($names, self::mayHandOut(...));

        foreach ($allowed as $name) {
            $holder->assign($name);
        }

        if ($allowed !== []) {
            Bouncer::refresh();
        }
    }

    /** @return array<string, string> */
    private static function options(): array
    {
        /** @var array<string, string> $options */
        $options = Models::role()->newQuery()->orderBy('name')->pluck('name', 'name')->all();

        return $options;
    }

    private static function mayHandOut(string $name): bool
    {
        $privileged = resolve(PrivilegedRole::class);
        if (! $privileged->isNamed($name)) {
            return true;
        }

        return $privileged->mayBeHandedOutBy(auth()->user());
    }
}
