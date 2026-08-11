<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Comment;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

function changedRow(string $name, ?string $entityType = null): Model
{
    /** @var Model $row */
    $row = Models::ability()->newQuery()
        ->where('name', $name)
        ->where('entity_type', $entityType)
        ->whereNull('entity_id')
        ->where('only_owned', false)
        ->firstOrFail();

    return $row;
}

function changedRole(string $name = 'editor'): Model
{
    /** @var Model $role */
    $role = Models::role()->newQuery()->create(['name' => $name]);

    return $role;
}

function ownedRow(): Model
{
    $row = Models::ability();

    $row->forceFill([
        'name' => 'update',
        'entity_type' => Post::class,
        'entity_id' => null,
        'only_owned' => true,
        'title' => 'Posts: change your own',
    ])->save();

    return $row;
}

function holderPath(Model $role): string
{
    /** @var int|string $key */
    $key = $role->getKey();

    return 'data.'.AbilityForm::HOLDERS.'.'.$key;
}

function rolePermissionCount(): int
{
    return DB::table(Models::table('permissions'))
        ->where('entity_type', Models::role()->getMorphClass())
        ->count();
}

test('the title is the only field this screen writes', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $row = changedRow('update', Post::class);

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->set('data.title', 'Changed by a person')
        ->set('data.name', 'sing-a-song')
        ->set('data.entity_type', Comment::class)
        ->call('save')
        ->assertHasNoFormErrors();

    $row->refresh();

    expect($row->getAttribute('title'))->toBe('Changed by a person')
        ->and($row->getAttribute('name'))->toBe('update')
        ->and($row->getAttribute('entity_type'))->toBe(Post::class);
});

test('a stance set here is the row the roles screen writes', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();
    $row = changedRow('update', Post::class);

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->set(holderPath($role), Stance::Granted->value)
        ->call('save')
        ->assertHasNoFormErrors();

    $state = app(RoleAbilities::class)->toFormState($role);

    expect($state[Subject::keyFor(Post::class)]['update'] ?? null)->toBe(Stance::Granted->value)
        ->and(holds($role, 'update', Post::class))->toBeTrue();
});

test('a denial set here is carried as a denial', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();
    $row = changedRow('update', Post::class);

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->set(holderPath($role), Stance::Forbidden->value)
        ->call('save');

    Bouncer::allow($role)->to('update', Post::class);
    Bouncer::refresh();

    expect(holds($role, 'update', Post::class))->toBeFalse();
});

test('a narrowed rule is handed out as itself and leaves the plain one alone', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();
    $owned = ownedRow();

    livewire(EditAbility::class, ['record' => $owned->getKey()])
        ->set(holderPath($role), Stance::Granted->value)
        ->call('save')
        ->assertHasNoFormErrors();

    $abilities = app(RoleAbilities::class);

    expect($abilities->stanceOnRow($role, $owned))->toBe(Stance::Granted)
        ->and($abilities->stanceOnRow($role, changedRow('update', Post::class)))->toBe(Stance::Neutral)
        ->and($abilities->toFormState($role)[Subject::keyFor(Post::class)]['update'] ?? null)
        ->toBe(Stance::Neutral->value);
});

test('a rule may be taken back, and the screen says it wrote what it wrote', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();
    $row = changedRow('update', Post::class);

    grant($role, [['update', Post::class]]);

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->set(holderPath($role), Stance::Neutral->value)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(__('filament-bouncer::abilities.saved'));

    expect(app(RoleAbilities::class)->stanceOnRow($role, $row))->toBe(Stance::Neutral)
        ->and(holds($role, 'update', Post::class))->toBeFalse();
});

test('the screen arrives holding what each role already says', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();
    $row = changedRow('update', Post::class);

    grant($role, [['update', Post::class]]);

    /** @var array<string, string> $state */
    $state = livewire(EditAbility::class, ['record' => $row->getKey()])->get('data.'.AbilityForm::HOLDERS);

    /** @var int|string $key */
    $key = $role->getKey();

    expect($state[(string) $key] ?? null)->toBe(Stance::Granted->value);
});

test('a row the code no longer declares carries no cells', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    changedRole();

    Bouncer::allow('reviewer')->to('sing-a-song');

    livewire(EditAbility::class, ['record' => changedRow('sing-a-song')->getKey()])
        ->assertSee(__('filament-bouncer::abilities.form.withheld'))
        ->assertDontSeeHtml('class="fb-seg"');
});

test('a row the code still declares carries them', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    changedRole();

    livewire(EditAbility::class, ['record' => changedRow('update', Post::class)->getKey()])
        ->assertDontSee(__('filament-bouncer::abilities.form.withheld'))
        ->assertSeeHtml('class="fb-seg"');
});

test('a row nobody declares any more takes no stance from a request that arms one', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();

    Bouncer::allow('reviewer')->to('sing-a-song');

    $row = changedRow('sing-a-song');

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->set(holderPath($role), Stance::Granted->value)
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(RoleAbilities::class)->stanceOnRow($role, $row))->toBe(Stance::Neutral)
        ->and(holds($role, 'sing-a-song'))->toBeFalse();
});

test('a role deleted while the screen sat open is passed over and not brought back', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $role = changedRole();
    $row = changedRow('update', Post::class);

    $page = livewire(EditAbility::class, ['record' => $row->getKey()])
        ->set(holderPath($role), Stance::Granted->value);

    Models::role()->newQuery()->whereKey($role->getKey())->delete();

    $page->call('save')->assertHasNoFormErrors();

    expect(Models::role()->newQuery()->where('name', 'editor')->exists())->toBeFalse()
        ->and(rolePermissionCount())->toBe(0);
});

test('a delete armed by hand on the changing screen leaves the row standing', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    $row = changedRow('update', Post::class);

    livewire(EditAbility::class, ['record' => $row->getKey()])
        ->assertActionDoesNotExist(TestAction::make('delete'))
        ->call('mountAction', 'delete')
        ->call('callMountedAction');

    expect(Models::ability()->newQuery()->whereKey($row->getKey())->exists())->toBeTrue();
});

test('the heading says what the reconciliation has to say about the row', function (): void {
    signInAsAbilityManager();
    reconcileStore();

    livewire(EditAbility::class, ['record' => changedRow('update', Post::class)->getKey()])
        ->assertSee(__('filament-bouncer::abilities.declared.declared_note'));
});
