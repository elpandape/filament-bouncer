<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Console;

use ElPandaPe\FilamentBouncer\Events\RoleAssignedEvent;
use ElPandaPe\FilamentBouncer\Support\Causer;
use ElPandaPe\FilamentBouncer\Support\RoleHolding;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Database\Models;

/**
 * Hands a role to somebody from the command line.
 *
 * This is the last step of the way back in. The reconcile command makes sure the role
 * that holds everything exists, but a role nobody holds opens no doors, and reaching for
 * tinker to undo a lockout means reaching for it while locked out.
 */
final class AssignCommand extends Command
{
    protected $signature = 'filament-bouncer:assign
        {role : The name of the role to hand over}
        {user : The key, or the email address, of whoever is to hold it}';

    protected $description = 'Hand a role to somebody, without going through tinker';

    public function handle(Bouncer $bouncer): int
    {
        /** @var string $name */
        $name = $this->argument('role');

        /** @var string $identifier */
        $identifier = $this->argument('user');

        // Bouncer creates a role it cannot find rather than complaining, so a misspelling
        // would otherwise leave somebody holding a brand new role that grants nothing at
        // all, under a line of output saying it worked.
        if (! Models::role()->newQuery()->where('name', $name)->exists()) {
            $this->components->error(sprintf('There is no role called [%s]. Nothing was assigned.', $name));

            return self::FAILURE;
        }

        $user = $this->find($identifier);

        if (! $user instanceof Model) {
            $this->components->error(sprintf('No user answers to [%s].', $identifier));

            return self::FAILURE;
        }

        // Asked before the write: assign() is idempotent, and afterwards the pivot row
        // exists either way, leaving nothing to tell whether it was already there.
        $alreadyHeld = RoleHolding::of($user, $name);

        $bouncer->assign($name)->to($user);
        $bouncer->refresh();

        if (! $alreadyHeld) {
            event(new RoleAssignedEvent($user, $name, Causer::current()));
        }

        $this->components->info(sprintf('[%s] now holds the role [%s].', $identifier, $name));

        return self::SUCCESS;
    }

    /**
     * An email address is what somebody locked out knows about themselves; a key is what
     * a script has to hand. Which was meant is decided by the shape of what was typed,
     * rather than by trying both, because a key column of integers asked to match an
     * email address is an error on PostgreSQL rather than an empty result.
     */
    private function find(string $identifier): ?Model
    {
        $user = Models::user();

        if (str_contains($identifier, '@') && Schema::hasColumn($user->getTable(), 'email')) {
            return $user->newQuery()->where('email', $identifier)->first();
        }

        return $user->newQuery()->whereKey($identifier)->first();
    }
}
