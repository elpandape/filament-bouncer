<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\EditableCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Contracts\Scope;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

/**
 * What a role says about each ability, and how the roles screen changes it.
 */
final readonly class RoleAbilities
{
    public function __construct(
        private Bouncer $bouncer,
        private EditableCatalog $editable,
    ) {}

    /**
     * The role's stances, shaped the way the form holds them.
     *
     * @return array<string, array<string, string>>
     */
    public function toFormState(Model $role): array
    {
        $stances = $this->stances($role);
        $state = [];

        foreach ($this->editable->current()->subjects as $key => $subject) {
            foreach ($subject->abilities as $action => $ability) {
                $state[$key][$action] = ($stances[$ability->identity()] ?? Stance::Neutral)->value;
            }
        }

        return $state;
    }

    /**
     * Whether the role answers yes to an ability once every rule it holds is taken
     * into account, and not merely the row that names the ability exactly.
     *
     * A role granted `everything()` holds no row for `view` on anything, and yet
     * answers yes to all of them. The grid reads the exact rows, because those are
     * what it writes back; this reads what Bouncer would actually answer, so the two
     * together can say the one thing a grid alone cannot: that a role holds something
     * nobody handed it.
     *
     * The clipboard is asked rather than the identifiers being matched here by hand,
     * so that the answer on screen and the answer at the Gate come from the same code.
     */
    public function holds(Model $role, Ability $ability): bool
    {
        return $this->bouncer->getClipboard()->check($role, $ability->name, $ability->entityType);
    }

    /**
     * The rules the grid never offers, counted per ability.
     *
     * These are the rows the stances deliberately skip: the ones naming a single record
     * and the ones covering only what their holder owns. The grid cannot write them and
     * must not remove them, so the one thing left to do is say they are there.
     *
     * @return array<string, Restriction>
     */
    public function restrictions(Model $role): array
    {
        $abilities = Models::table('abilities');

        $rows = $this->rows($role)
            ->where(static function (Builder $query) use ($abilities): void {
                $query->whereNotNull($abilities.'.entity_id')
                    ->orWhere($abilities.'.only_owned', true);
            })
            ->get([
                $abilities.'.name as name',
                $abilities.'.entity_type as entity_type',
                $abilities.'.only_owned as only_owned',
            ]);

        $restrictions = [];

        foreach ($rows as $row) {
            /** @var string $name */
            $name = $row->getAttribute('name');

            /** @var string|null $entityType */
            $entityType = $row->getAttribute('entity_type');

            $identity = Ability::identityFor($name, $entityType);
            $restriction = $restrictions[$identity] ?? new Restriction;

            $restrictions[$identity] = $row->getAttribute('only_owned')
                ? $restriction->withOwned()
                : $restriction->withRecord();
        }

        return $restrictions;
    }

    /**
     * Bring the role's stances in line with what the form was saved holding.
     *
     * The incoming state is never walked. Everything is driven off the catalogue this
     * authority may decide about, so a cell smuggled into the request for an ability
     * they do not hold has nothing to match against and changes nothing — and a stance
     * they cannot see is never overwritten either.
     *
     * Forbidding is treated exactly like granting, and is offered on the same abilities
     * and no others. Forbidding looks like a smaller power than granting, but a denial
     * you are unable to lift afterwards is a way to lock somebody out of something you
     * were never trusted with, so both go through the same gate.
     *
     * @param  array<string, array<string, string>>  $state
     */
    public function save(Model $role, array $state): void
    {
        $stances = $this->stances($role);

        foreach ($this->editable->current()->subjects as $key => $subject) {
            foreach ($subject->abilities as $action => $ability) {
                $current = $stances[$ability->identity()] ?? Stance::Neutral;
                $wanted = Stance::tryFrom((string) ($state[$key][$action] ?? '')) ?? Stance::Neutral;

                if ($wanted === $current) {
                    continue;
                }

                $this->clear($role, $ability);
                $this->apply($role, $ability, $wanted);
            }
        }

        // Bouncer invalidates nothing of its own accord, so without this the screen
        // would repaint from the state it held before the save.
        $this->bouncer->refresh();
    }

    /**
     * Takes both kinds of row away before the new stance is written.
     *
     * Bouncer keeps granting and forbidding in separate rows and lets a role hold both
     * at once, so clearing only the one that was read back would leave the other behind
     * and the next read would disagree with the screen that wrote it.
     */
    private function clear(Model $role, Ability $ability): void
    {
        $this->bouncer->disallow($role)->to($ability->name, $ability->entityType);
        $this->bouncer->unforbid($role)->to($ability->name, $ability->entityType);
    }

    private function apply(Model $role, Ability $ability, Stance $stance): void
    {
        if ($stance === Stance::Granted) {
            $this->bouncer->allow($role)->to($ability->name, $ability->entityType);
        }

        if ($stance === Stance::Forbidden) {
            $this->bouncer->forbid($role)->to($ability->name, $ability->entityType);
        }
    }

    /**
     * What the role says about each ability it has a row for.
     *
     * The pivot is joined by hand rather than read through the role's own relation,
     * because the role model is whatever the application configured and nothing
     * promises the analyser that it carries Bouncer's traits.
     *
     * @return array<string, Stance>
     */
    private function stances(Model $role): array
    {
        $abilities = Models::table('abilities');
        $permissions = Models::table('permissions');

        $rows = $this->rows($role)
            // Only the plain row, because the plain row is the only one the grid writes.
            // Bouncer keeps a grant over a whole model, a grant over one record and a
            // grant over what the holder owns in three separate rows, and the conductor
            // behind `allow()->to($name, Post::class)` matches exactly the first of them
            // (`entity_id` null, `only_owned` false). Reading the other two here would
            // put the cell in a state it has no way to write back: turning it off would
            // remove nothing and the screen would repaint the stance it just cleared.
            ->whereNull($abilities.'.entity_id')
            ->where($abilities.'.only_owned', false)
            ->get([
                $abilities.'.name as name',
                $abilities.'.entity_type as entity_type',
                $permissions.'.forbidden as forbidden',
            ]);

        $stances = [];

        foreach ($rows as $row) {
            /** @var string $name */
            $name = $row->getAttribute('name');

            /** @var string|null $entityType */
            $entityType = $row->getAttribute('entity_type');

            $stance = $row->getAttribute('forbidden') ? Stance::Forbidden : Stance::Granted;

            $identity = Ability::identityFor($name, $entityType);

            // A role is allowed to hold both rows at once, and Bouncer answers no when it
            // does, so the screen has to say the same.
            if (($stances[$identity] ?? null) !== Stance::Forbidden) {
                $stances[$identity] = $stance;
            }
        }

        return $stances;
    }

    /**
     * Every ability row this role has a permission for.
     *
     * The pivot is joined by hand rather than read through the role's own relation,
     * because the role model is whatever the application configured and nothing
     * promises the analyser that it carries Bouncer's traits.
     *
     * @return Builder<StoredAbility>
     */
    private function rows(Model $role): Builder
    {
        $permissions = Models::table('permissions');

        $query = Models::ability()->newQuery()
            ->join($permissions, $permissions.'.ability_id', '=', Models::table('abilities').'.id')
            ->where($permissions.'.entity_id', $role->getKey())
            ->where($permissions.'.entity_type', $role->getMorphClass());

        /** @var Scope $scope */
        $scope = Models::scope();
        $scope->applyToRelationQuery($query, $permissions);

        return $query;
    }
}
