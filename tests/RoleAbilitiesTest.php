<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    $this->post = Entity::keyFor(Post::class);
});

function editor(): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => 'editor']);

    return $role;
}

/**
 * The part of the grid this file is about. The signed-in authority also holds the
 * abilities of the roles screen itself, so the whole state carries a row for those too.
 *
 * @return array<string, string>
 */
function postState(Model $role): array
{
    return app(RoleAbilities::class)->toFormState($role)[Entity::keyFor(Post::class)];
}

/**
 * @param  array<string, Stance>  $cells
 */
function saveStances(Model $role, array $cells): void
{
    app(RoleAbilities::class)->save($role, [
        Entity::keyFor(Post::class) => array_map(static fn (Stance $stance): string => $stance->value, $cells),
    ]);
}

function roleAbilities(): RoleAbilities
{
    return app(RoleAbilities::class);
}

/**
 * The catalogued ability behind one of the Post cells. It is what the store is asked about
 * and what names the entries `restrictions()` comes back with, so composing the same
 * identity here by hand would prove nothing about the pair matching up.
 */
function postAbility(string $action): Ability
{
    /** @var Ability $ability */
    $ability = app(CatalogRegistry::class)->current()->entity(Entity::keyFor(Post::class))?->ability($action);

    return $ability;
}

/**
 * A stored row on its own, with nobody holding it: the kind the abilities screen composes
 * and the grid never offers, which is the only kind `stanceOnRow()` and `saveRow()` exist
 * for.
 */
function abilityRow(string $name, ?int $record = null, bool $owned = false): Model
{
    /** @var Model $row */
    $row = Models::ability()->newQuery()->make();

    $row->forceFill([
        'name' => $name,
        'title' => 'Posts, written by hand',
        'entity_type' => Post::class,
        'entity_id' => $record,
        'only_owned' => $owned,
    ])->save();

    return $row;
}

test('a forbidden ability beats a grant the same role was given', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();
    grant($role, [['viewAny', Post::class]]);

    saveStances($role, ['viewAny' => Stance::Forbidden]);

    expect(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(postState($role)['viewAny'])->toBe(Stance::Forbidden->value);
});

test('a forbidden ability beats a grant somebody holds from anywhere else', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $reader = editor();
    $forbidder = Models::role()->newQuery()->create(['name' => 'restricted']);

    $user = signInAsRoleManager();
    grant($user, [['viewAny', Post::class]]);

    Bouncer::assign('editor')->to($user);
    Bouncer::assign('restricted')->to($user);
    grant($reader, [['viewAny', Post::class]]);
    Bouncer::forbid($forbidder)->to('viewAny', Post::class);
    Bouncer::refresh();

    expect(holds($user, 'viewAny', Post::class))->toBeFalse();
});

test('lifting a denial hands the ability back', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();

    saveStances($role, ['viewAny' => Stance::Forbidden]);
    expect(holds($role, 'viewAny', Post::class))->toBeFalse();

    saveStances($role, ['viewAny' => Stance::Granted]);
    expect(holds($role, 'viewAny', Post::class))->toBeTrue();

    saveStances($role, ['viewAny' => Stance::Neutral]);
    expect(postState($role)['viewAny'])->toBe(Stance::Neutral->value);
});

test('a role holding both rows at once reads back as forbidden', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();

    Bouncer::allow($role)->to('viewAny', Post::class);
    Bouncer::forbid($role)->to('viewAny', Post::class);
    Bouncer::refresh();

    expect(postState($role)['viewAny'])->toBe(Stance::Forbidden->value);
});

test('it takes back a grant whose cell went neutral', function (): void {
    $role = editor();
    grant($role, [['viewAny', Post::class]]);

    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    saveStances($role, ['viewAny' => Stance::Neutral]);

    expect(holds($role, 'viewAny', Post::class))->toBeFalse();
});

test('reading a role back gives the grid the shape the form holds', function (): void {
    $role = editor();
    grant($role, [['create', Post::class]]);

    grant(signInAsRoleManager(), [['viewAny', Post::class], ['create', Post::class]]);

    expect(postState($role))->toBe([
        'manage' => Stance::Neutral->value,
        'create' => Stance::Granted->value,
        'delete' => Stance::Neutral->value,
        'forceDelete' => Stance::Neutral->value,
        'update' => Stance::Neutral->value,
        'view' => Stance::Neutral->value,
        'viewAny' => Stance::Neutral->value,
    ]);
});

test('a grant for only what the role owns is not read as a grant over everything', function (): void {
    grant(signInAsRoleManager(), [['delete', Post::class]]);

    $role = editor();
    Bouncer::allow($role)->toOwn(Post::class)->to('delete');
    Bouncer::refresh();

    expect(postState($role)['delete'])->toBe(Stance::Neutral->value);
});

test('a grant about one record is not read as a grant over all of them', function (): void {
    grant(signInAsRoleManager(), [['delete', Post::class]]);

    $role = editor();
    Bouncer::allow($role)->to('delete', Post::forceCreate([]));
    Bouncer::refresh();

    expect(postState($role)['delete'])->toBe(Stance::Neutral->value);
});

test('the grid leaves alone the rules it never offered', function (): void {
    grant(signInAsRoleManager(), [['delete', Post::class]]);

    $role = editor();
    Bouncer::allow($role)->toOwn(Post::class)->to('delete');
    Bouncer::refresh();

    saveStances($role, ['delete' => Stance::Neutral]);

    expect(restrictedRows($role))->toBe(1);
});

/**
 * The rows about this ability that the grid never offers: the ones naming a record and
 * the ones covering only what their holder owns.
 */
function restrictedRows(Model $role): int
{
    return Models::ability()->newQuery()
        ->join(Models::table('permissions'), Models::table('permissions').'.ability_id', '=', Models::table('abilities').'.id')
        ->where(Models::table('permissions').'.entity_id', $role->getKey())
        ->where(Models::table('permissions').'.entity_type', $role->getMorphClass())
        ->where(static function ($query): void {
            $query->whereNotNull(Models::table('abilities').'.entity_id')
                ->orWhere(Models::table('abilities').'.only_owned', true);
        })
        ->count();
}

test('the grid hands out what the person filling it in does not hold themselves', function (): void {
    signInAsRoleManager();

    $role = editor();

    saveStances($role, ['forceDelete' => Stance::Granted]);

    expect(holds($role, 'forceDelete', Post::class))->toBeTrue();
});

test('a cell for something the panel does not declare still changes nothing', function (): void {
    signInAsRoleManager();

    $role = editor();

    app(RoleAbilities::class)->save($role, ['invented' => ['byHand' => Stance::Granted->value]]);

    expect(abilityCount($role))->toBe(0);
});

test('an untouched cell is left exactly where it was', function (): void {
    signInAsRoleManager();

    $role = editor();
    grant($role, [['forceDelete', Post::class]]);

    saveStances($role, ['viewAny' => Stance::Granted]);

    expect(holds($role, 'forceDelete', Post::class))->toBeTrue();
});

test('the grant covering a whole model is read into the cell of its own', function (): void {
    $role = editor();

    Bouncer::allow($role)->toManage(Post::class);
    Bouncer::refresh();

    expect(postState($role)['manage'])->toBe(Stance::Granted->value)
        ->and(postState($role)['viewAny'])->toBe(Stance::Neutral->value);
});

test('a role granted everything holds abilities no row of its own names', function (): void {
    $role = editor();

    Bouncer::allow($role)->everything();
    Bouncer::refresh();

    expect(roleAbilities()->holds($role, postAbility('view')))->toBeTrue()
        ->and(postState($role)['view'])->toBe(Stance::Neutral->value);
});

test('it answers no about an ability nothing gave the role, and yes once something did', function (): void {
    $role = editor();

    expect(roleAbilities()->holds($role, postAbility('view')))->toBeFalse();

    grant($role, [['view', Post::class]]);

    expect(roleAbilities()->holds($role, postAbility('view')))->toBeTrue();
});

test('a rule for only what the role owns is reported as a restriction', function (): void {
    $role = editor();

    Bouncer::allow($role)->toOwn(Post::class)->to('delete');
    grant($role, [['viewAny', Post::class]]);

    $restrictions = roleAbilities()->restrictions($role);
    $restriction = $restrictions[postAbility('delete')->identity()] ?? null;

    expect($restriction?->owned)->toBeTrue()
        ->and($restriction?->records)->toBe(0)
        ->and($restrictions[postAbility('viewAny')->identity()] ?? null)->toBeNull();
});

test('rules about single records are counted one by one', function (): void {
    $role = editor();

    Bouncer::allow($role)->to('delete', Post::forceCreate([]));
    Bouncer::allow($role)->to('delete', Post::forceCreate([]));
    Bouncer::refresh();

    $restriction = roleAbilities()->restrictions($role)[postAbility('delete')->identity()] ?? null;

    expect($restriction?->records)->toBe(2)
        ->and($restriction?->owned)->toBeFalse();
});

test('what a role owns and the records it names come back under one entry', function (): void {
    $role = editor();

    Bouncer::allow($role)->toOwn(Post::class)->to('delete');
    Bouncer::allow($role)->to('delete', Post::forceCreate([]));
    Bouncer::refresh();

    $restriction = roleAbilities()->restrictions($role)[postAbility('delete')->identity()] ?? null;

    expect($restriction?->owned)->toBeTrue()
        ->and($restriction?->records)->toBe(1);
});

test('a role whose every rule is plain has no restrictions to report', function (): void {
    $role = editor();

    grant($role, [['viewAny', Post::class], ['delete', Post::class]]);

    expect(roleAbilities()->restrictions($role))->toBeEmpty();
});

test('a stored row is read as itself and not as the plain row of the same name', function (): void {
    $role = editor();
    $plain = abilityRow('viewAny');
    $narrowed = abilityRow('viewAny', owned: true);

    Bouncer::allow($role)->to($narrowed);
    Bouncer::refresh();

    expect(roleAbilities()->stanceOnRow($role, $narrowed))->toBe(Stance::Granted)
        ->and(roleAbilities()->stanceOnRow($role, $plain))->toBe(Stance::Neutral)
        ->and(postState($role)['viewAny'])->toBe(Stance::Neutral->value);
});

test('a row nobody handed the role reads back neutral', function (): void {
    expect(roleAbilities()->stanceOnRow(editor(), abilityRow('viewAny')))->toBe(Stance::Neutral);
});

test('a row a role holds both ways reads back as forbidden', function (): void {
    $role = editor();
    $row = abilityRow('delete', owned: true);

    Bouncer::allow($role)->to($row);
    Bouncer::forbid($role)->to($row);
    Bouncer::refresh();

    expect(roleAbilities()->stanceOnRow($role, $row))->toBe(Stance::Forbidden);
});

test('a stance saved on one row leaves the row beside it alone', function (): void {
    $role = editor();
    $plain = abilityRow('viewAny');
    $narrowed = abilityRow('viewAny', owned: true);

    roleAbilities()->saveRow($role, $narrowed, Stance::Granted);

    expect(roleAbilities()->stanceOnRow($role, $narrowed))->toBe(Stance::Granted)
        ->and(roleAbilities()->stanceOnRow($role, $plain))->toBe(Stance::Neutral)
        ->and(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(abilityCount($role))->toBe(1);
});

test('saving the stance a row already holds leaves it exactly as it was', function (): void {
    $role = editor();
    $row = abilityRow('delete', record: 3);

    roleAbilities()->saveRow($role, $row, Stance::Forbidden);
    roleAbilities()->saveRow($role, $row, Stance::Forbidden);

    expect(roleAbilities()->stanceOnRow($role, $row))->toBe(Stance::Forbidden)
        ->and(abilityCount($role))->toBe(1);
});

test('a row set the other way is swapped and not stacked', function (): void {
    $role = editor();
    $row = abilityRow('delete', record: 3);

    roleAbilities()->saveRow($role, $row, Stance::Granted);
    roleAbilities()->saveRow($role, $row, Stance::Forbidden);

    expect(roleAbilities()->stanceOnRow($role, $row))->toBe(Stance::Forbidden)
        ->and(abilityCount($role))->toBe(1);
});

test('a row set back to neutral is taken away', function (): void {
    $role = editor();
    $row = abilityRow('delete', record: 3);

    roleAbilities()->saveRow($role, $row, Stance::Granted);
    roleAbilities()->saveRow($role, $row, Stance::Neutral);

    expect(roleAbilities()->stanceOnRow($role, $row))->toBe(Stance::Neutral)
        ->and(abilityCount($role))->toBe(0);
});

test('a stance saved on a row is answered by the Gate without another refresh', function (): void {
    $role = editor();
    $row = abilityRow('viewAny');

    expect(holds($role, 'viewAny', Post::class))->toBeFalse();

    roleAbilities()->saveRow($role, $row, Stance::Granted);

    expect(holds($role, 'viewAny', Post::class))->toBeTrue();
});
