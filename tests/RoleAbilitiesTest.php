<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
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

test('it ignores a cell for an ability the authority does not hold', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    $role = editor();

    app(RoleAbilities::class)->save($role, [
        $this->post => ['viewAny' => true, 'forceDelete' => true],
    ]);

    expect(holds($role, 'viewAny', Post::class))->toBeTrue()
        ->and(holds($role, 'forceDelete', Post::class))->toBeFalse();
});

test('it ignores a whole subject the authority holds nothing of', function (): void {
    grant(signIn(), [['viewAny', Post::class]]);

    $role = editor();

    app(RoleAbilities::class)->save($role, [
        'impersonate-users' => ['use' => true],
    ]);

    expect(holds($role, 'impersonate-users'))->toBeFalse();
});

test('it leaves a grant the authority cannot see exactly where it was', function (): void {
    $role = editor();
    grant($role, [['forceDelete', Post::class]]);

    grant(signIn(), [['viewAny', Post::class]]);

    app(RoleAbilities::class)->save($role, [$this->post => ['viewAny' => true]]);

    expect(holds($role, 'forceDelete', Post::class))->toBeTrue()
        ->and(holds($role, 'viewAny', Post::class))->toBeTrue();
});

test('it takes back a grant whose cell was cleared', function (): void {
    $role = editor();
    grant($role, [['viewAny', Post::class]]);

    grant(signIn(), [['viewAny', Post::class]]);

    app(RoleAbilities::class)->save($role, [$this->post => ['viewAny' => false]]);

    expect(holds($role, 'viewAny', Post::class))->toBeFalse();
});

test('reading a role back gives the grid the shape the form holds', function (): void {
    $role = editor();
    grant($role, [['create', Post::class]]);

    grant(signIn(), [['viewAny', Post::class], ['create', Post::class]]);

    expect(app(RoleAbilities::class)->toFormState($role))->toBe([
        $this->post => ['create' => true, 'viewAny' => false],
    ]);
});

test('an ability the role was forbidden does not read back as granted', function (): void {
    $role = editor();

    Bouncer::forbid($role)->to('viewAny', Post::class);
    Bouncer::refresh();

    grant(signIn(), [['viewAny', Post::class]]);

    expect(app(RoleAbilities::class)->toFormState($role))->toBe([
        $this->post => ['viewAny' => false],
    ]);
});
