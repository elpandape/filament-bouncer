<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    $this->post = Subject::keyFor(Post::class);
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
    return app(RoleAbilities::class)->toFormState($role)[Subject::keyFor(Post::class)];
}

/**
 * @param  array<string, Stance>  $cells
 */
function saveStances(Model $role, array $cells): void
{
    app(RoleAbilities::class)->save($role, [
        Subject::keyFor(Post::class) => array_map(static fn (Stance $stance): string => $stance->value, $cells),
    ]);
}

test('it ignores a cell for an ability the authority does not hold', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();

    saveStances($role, ['viewAny' => Stance::Granted, 'forceDelete' => Stance::Granted]);

    expect(holds($role, 'viewAny', Post::class))->toBeTrue()
        ->and(holds($role, 'forceDelete', Post::class))->toBeFalse();
});

test('it refuses to forbid an ability the authority does not hold either', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();

    saveStances($role, ['forceDelete' => Stance::Forbidden]);

    expect(postState($role))->toBe(['viewAny' => Stance::Neutral->value]);
});

test('it ignores a whole subject the authority holds nothing of', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();

    app(RoleAbilities::class)->save($role, ['impersonate-users' => ['use' => Stance::Granted->value]]);

    expect(holds($role, 'impersonate-users'))->toBeFalse();
});

test('it leaves a stance the authority cannot see exactly where it was', function (): void {
    $role = editor();
    grant($role, [['forceDelete', Post::class]]);

    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    saveStances($role, ['viewAny' => Stance::Granted]);

    expect(holds($role, 'forceDelete', Post::class))->toBeTrue();
});

test('a forbidden ability beats a grant the same role was given', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();
    grant($role, [['viewAny', Post::class]]);

    saveStances($role, ['viewAny' => Stance::Forbidden]);

    expect(holds($role, 'viewAny', Post::class))->toBeFalse()
        ->and(postState($role))->toBe(['viewAny' => Stance::Forbidden->value]);
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
    expect(postState($role))->toBe(['viewAny' => Stance::Neutral->value]);
});

test('a role holding both rows at once reads back as forbidden', function (): void {
    grant(signInAsRoleManager(), [['viewAny', Post::class]]);

    $role = editor();

    Bouncer::allow($role)->to('viewAny', Post::class);
    Bouncer::forbid($role)->to('viewAny', Post::class);
    Bouncer::refresh();

    expect(postState($role))->toBe(['viewAny' => Stance::Forbidden->value]);
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
        'create' => Stance::Granted->value,
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
