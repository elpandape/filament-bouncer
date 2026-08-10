<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Policies;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

/**
 * Who may read the abilities screen, and rename what it lists.
 *
 * There is deliberately no `create` and no `delete`. An ability is not a record somebody
 * makes: it is a method on a policy, a page, a widget or a name in configuration, and
 * `filament-bouncer:reconcile` is what writes it. A row made by hand would be one the
 * catalogue does not declare — `--check` would fail on it and `--prune` would delete it
 * — so offering to create one would be offering to make a mess the next deploy cleans up.
 *
 * `update` is offered because exactly one field is the reader's: the title. The name the
 * code asks the Gate and the model it asks about are declarations, and the form shows
 * them without letting them be touched.
 */
final class AbilityRowPolicy extends AbilityPolicy
{
    public function viewAny(Model $user): bool
    {
        return $this->allows($user, 'viewAny', $this->model());
    }

    public function view(Model $user, Model $ability): bool
    {
        return $this->allows($user, 'view', $ability);
    }

    public function update(Model $user, Model $ability): bool
    {
        return $this->allows($user, 'update', $ability);
    }

    /**
     * @return class-string<Ability>
     */
    private function model(): string
    {
        /** @var class-string<Ability> $model */
        $model = Models::classname(Ability::class);

        return $model;
    }
}
