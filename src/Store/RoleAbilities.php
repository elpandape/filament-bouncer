<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Events\AbilityRef;
use ElPandaPe\FilamentBouncer\Events\AbilityStanceChangedEvent;
use ElPandaPe\FilamentBouncer\Support\Causer;
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
        private CatalogRegistry $catalogs,
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

        foreach ($this->catalogs->current()->entities as $key => $entity) {
            foreach ($entity->cells() as $action => $ability) {
                $state[$key][$action] = ($stances[$ability->identity()] ?? Stance::Neutral)->value;
            }
        }

        return $state;
    }

    /**
     * Whether the role answers yes to an ability once every rule it holds is taken
     * into account, and not merely the row that names the ability exactly.
     *
     * A role granted `everything()` holds no row for `view` on anything and yet answers yes to
     * all of them. The grid reads the exact rows because those are what it writes back; this
     * reads what Bouncer would answer, which is how the two together say that a role holds
     * something nobody handed it.
     *
     * The clipboard is asked rather than the identifiers matched by hand, so the answer on
     * screen and the answer at the Gate come from the same code.
     */
    public function holds(Model $role, Ability $ability): bool
    {
        return $this->bouncer->getClipboard()->check($role, $ability->name, $ability->entityType);
    }

    /**
     * The rules the grid never offers, counted per ability.
     *
     * The rows the stances skip: the grid cannot write them and must not remove them, so the
     * one thing left is to say they are there.
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
     * What a role says about one stored row, rather than about a catalogued ability.
     *
     * The row is addressed by its key, so a narrowed ability — one about a single record,
     * or one covering only what its holder owns — is read exactly as it was written.
     * Going through the catalogue would have matched it to the plain row of the same
     * name, which is a different rule granting a great deal more.
     */
    public function stanceOnRow(Model $role, Model $ability): Stance
    {
        $permissions = Models::table('permissions');

        $rows = $this->rows($role)
            ->where(Models::table('abilities').'.id', $ability->getKey())
            ->get([$permissions.'.forbidden as forbidden']);

        $stance = Stance::Neutral;

        foreach ($rows as $row) {
            // A role may hold both rows at once, and Bouncer answers no when it does.
            if ($row->getAttribute('forbidden')) {
                return Stance::Forbidden;
            }

            $stance = Stance::Granted;
        }

        return $stance;
    }

    /**
     * Set what a role says about one stored row.
     *
     * Both kinds of row go first for the same reason they do on the grid: Bouncer keeps
     * granting and forbidding apart and lets a role hold both, so clearing one would
     * leave the other behind.
     */
    public function saveRow(Model $role, Model $ability, Stance $stance): void
    {
        $was = $this->stanceOnRow($role, $ability);

        if ($stance === $was) {
            return;
        }

        $this->bouncer->disallow($role)->to($ability);
        $this->bouncer->unforbid($role)->to($ability);

        if ($stance === Stance::Granted) {
            $this->bouncer->allow($role)->to($ability);
        }

        if ($stance === Stance::Forbidden) {
            $this->bouncer->forbid($role)->to($ability);
        }

        $this->bouncer->refresh();

        event(new AbilityStanceChangedEvent($role, AbilityRef::fromRow($ability), $was, $stance, Causer::current()));
    }

    /**
     * Bring the role's stances in line with what the form was saved holding.
     *
     * The incoming state is never walked: everything is driven off the catalogue, so a cell
     * smuggled into the request has nothing to match against.
     *
     * Who may be here at all is the policy's question and the only one — somebody it lets in
     * hands out every ability the panel declares, whether or not they hold it themselves.
     *
     * @param  array<string, array<string, string>>  $state
     */
    public function save(Model $role, array $state): void
    {
        $stances = $this->stances($role);
        $causer = Causer::current();

        // Held back until every cell is written and the clipboard is refreshed: a
        // listener asking the Gate mid-loop would be reading a grid that is only
        // partly applied, whichever cell it happens to be asked about.
        $changes = [];

        foreach ($this->catalogs->current()->entities as $key => $entity) {
            foreach ($entity->cells() as $action => $ability) {
                // A cell the state does not mention is one nobody was asked about, and
                // silence is not an instruction to clear it. This is what lets the
                // abilities screen write a single cell without taking every other
                // ability of that role down with it.
                if (! array_key_exists($action, is_array($state[$key] ?? null) ? $state[$key] : [])) {
                    continue;
                }

                $current = $stances[$ability->identity()] ?? Stance::Neutral;
                $wanted = Stance::tryFrom((string) ($state[$key][$action] ?? '')) ?? Stance::Neutral;

                if ($wanted === $current) {
                    continue;
                }

                $this->clear($role, $ability);
                $this->apply($role, $ability, $wanted);

                $changes[] = new AbilityStanceChangedEvent(
                    $role,
                    AbilityRef::fromCatalog($ability),
                    $current,
                    $wanted,
                    $causer,
                );
            }
        }

        // Bouncer invalidates nothing of its own accord, so without this the screen
        // would repaint from the state it held before the save.
        $this->bouncer->refresh();

        foreach ($changes as $change) {
            event($change);
        }
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
     * The pivot is joined by hand because the role model is whatever the application
     * configured, and nothing promises the analyser it carries Bouncer's traits.
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
     * The pivot is joined by hand because the role model is whatever the application
     * configured, and nothing promises the analyser it carries Bouncer's traits.
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
