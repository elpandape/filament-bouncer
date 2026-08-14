<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Policies;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

/**
 * Who may read the abilities screen, rename what it lists, and narrow an ability.
 *
 * `create` makes exactly one kind of row: a narrowed one, about a single record or about
 * what its holder owns. Those are the rows the reconciliation deliberately never speaks
 * for, so `--check` does not fail on them and `--prune` does not take them away. The
 * plain row stays out of reach, because that one the catalogue owns: it is a method on a
 * policy, a page, a widget or a name in configuration, and `filament-bouncer:reconcile`
 * is what writes it.
 *
 * There is deliberately no `delete`. A row is only ever removed by the reconciliation
 * that stopped declaring it, and taking one away here would take every grant pointing at
 * it with no second question asked.
 *
 * `update` is offered because exactly one field is the reader's: the title. The name the
 * code asks the Gate and the model it asks about are declarations, and the form shows
 * them without letting them be touched.
 */
final class AbilityPolicy extends BouncerPolicy
{
    public function viewAny(Model $user): bool
    {
        return $this->allows($user, 'viewAny', $this->model());
    }

    public function create(Model $user): bool
    {
        return $this->allows($user, 'create', $this->model());
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
