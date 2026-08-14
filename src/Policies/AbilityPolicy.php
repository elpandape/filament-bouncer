<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Policies;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

/**
 * Who may read the abilities screen, rename what it lists, and narrow an ability.
 *
 * `create` makes one kind of row: a narrowed one, which the reconciliation never speaks for. The
 * plain row belongs to the catalogue and `filament-bouncer:reconcile` writes it.
 *
 * There is deliberately no `delete`: a row goes only when the reconciliation stops declaring it,
 * and taking one away here would take every grant pointing at it with no second question.
 *
 * `update` is offered because one field is the reader's — the title.
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
