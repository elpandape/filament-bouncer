<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Forms\DerivedTitle;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Actions\ProbeAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\CreateAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\EditAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ListAbilities;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages\ViewAbility;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\Ailment;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    signInAsAbilityManager();

    // The screens read back what the catalogue declares, so without this the store holds only the
    // four rows the sign-in granted and every row about a model is missing.
    reconcileStore();
});

/**
 * @param  array<string, mixed>  $columns
 */
function stored(array $columns = []): Model
{
    $ability = Models::ability();

    $ability->forceFill([...['name' => 'archive', 'only_owned' => false], ...$columns])->save();

    return $ability;
}

function declaredAbility(): Model
{
    /** @var Model $ability */
    // Asked by morph class rather than by the class name: Bouncer keeps morph classes, and its own
    // models carry an alias — handing that alias back as a class is how it dies.
    $ability = Models::ability()->newQuery()
        ->where('name', 'view')
        ->where('entity_type', (new Post)->getMorphClass())
        ->firstOrFail();

    return $ability;
}

test('the screen announces itself where the configuration says', function (): void {
    config()->set('filament-bouncer.abilities.icon', 'heroicon-o-key');
    config()->set('filament-bouncer.abilities.sort', 3);
    config()->set('filament-bouncer.navigation.group', 'Security');

    expect(AbilityResource::getNavigationIcon())->toBe('heroicon-o-key')
        ->and(AbilityResource::getNavigationSort())->toBe(3)
        ->and(AbilityResource::getNavigationGroup())->toBe('Security')
        ->and(AbilityResource::getModelLabel())->not->toBeEmpty()
        ->and(AbilityResource::getPluralModelLabel())->not->toBeEmpty()
        ->and(AbilityResource::getRecordTitleAttribute())->toBe('name');
});

test('the listing respects the tenant scope where the installation uses one', function (): void {
    config()->set('filament-bouncer.tenancy', true);

    $hidden = stored(['name' => 'hidden-one', 'scope' => 7]);

    livewire(ListAbilities::class)
        ->searchTable('hidden-one')
        ->assertCanNotSeeTableRecords([$hidden])
        ->assertOk();
});

test('the probe offers the accounts as well as the roles, and asks about any model', function (): void {
    $wildcard = stored(['name' => 'sweep', 'entity_type' => '*']);

    // Made rather than signed in as: signing in would swap the ability manager for somebody who
    // cannot reach this screen at all.
    // Nameless on purpose: the pull-down falls back to the handle rather than showing a blank row.
    $account = User::forceCreate([
        'name' => '',
        'email' => 'probe@example.test',
        'password' => 'irrelevant',
    ]);

    // Granted through Bouncer rather than the helper: the wildcard is not a class name, which is
    // all the helper's signature accepts.
    Bouncer::allow($account)->to('sweep', '*');
    Bouncer::refresh();

    expect(ProbeAbility::isAskable($wildcard))->toBeTrue()
        ->and(ProbeAbility::answers($wildcard, 'user:'.keyOf($account), null))->toBeTrue();

    livewire(ViewAbility::class, ['record' => $wildcard->getRouteKey()])
        ->mountAction(TestAction::make('probe'))
        // The accounts are their own group, kept apart from the roles so that "this role grants" is
        // never read as "this person can" — the very difference the probe exists to show.
        ->assertSchemaComponentExists(ProbeAbility::HOLDER, checkComponentUsing: function (Select $field) use ($account): bool {
            $accounts = $field->getOptions()[__('filament-bouncer::abilities.probe.accounts')] ?? null;

            return is_array($accounts) && array_key_exists('user:'.keyOf($account), $accounts);
        });
});

test('the listing shows what the reconciliation wrote', function (): void {
    livewire(ListAbilities::class)
        // Searched rather than paged through: the catalogue writes a row per policy method, so the
        // one being asserted is rarely on the first page.
        ->searchTable(class_basename(Post::class))
        ->assertCanSeeTableRecords([declaredAbility()])
        ->assertOk();
});

test('the listing offers no way at all to delete a rule', function (): void {
    livewire(ListAbilities::class)
        ->assertActionDoesNotExist(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertActionVisible(TestAction::make('narrow')->table(declaredAbility()))
        ->assertOk();
});

test('the record page offers no way at all to delete a rule', function (): void {
    livewire(EditAbility::class, ['record' => declaredAbility()->getRouteKey()])
        ->assertActionDoesNotExist(DeleteAction::class)
        ->assertOk();
});

test('the resource refuses every question Filament asks about deleting one', function (): void {
    expect(AbilityResource::canDelete(declaredAbility()))->toBeFalse()
        ->and(AbilityResource::canDeleteAny())->toBeFalse();
});

test('the listing reads how far a rule reaches, and says nothing where it reaches everything', function (): void {
    $post = Post::forceCreate(['title' => 'Fenced']);

    stored(['entity_type' => Post::class, 'entity_id' => $post->getKey()]);
    stored(['name' => 'sweep', 'entity_type' => '*']);

    $page = livewire(ListAbilities::class)->searchTable('archive');
    $page->assertOk();

    expect($page->html())->toContain(__('filament-bouncer::abilities.table.reach_record', ['id' => keyOf($post)]));
});

test('the listing draws a rule in each of the healths the column can paint', function (): void {
    $hidden = stored(['name' => 'hidden-one', 'scope' => 7]);
    $ghost = stored(['name' => 'ghost-one', 'entity_type' => 'App\\Models\\Gone']);

    livewire(ListAbilities::class)
        ->assertCanSeeTableRecords([$hidden, $ghost])
        ->assertOk();

    expect(resolve(Diagnosis::class)->severity($hidden))->toBe(Diagnosis::HIDDEN)
        ->and(resolve(Diagnosis::class)->severity($ghost))->toBe(Diagnosis::SEVERE);
});

test('the listing shows only the rules suffering the ailment it is filtered by', function (): void {
    $hidden = stored(['name' => 'hidden-one', 'scope' => 7]);

    livewire(ListAbilities::class)
        ->filterTable('health', Ailment::Invisible->value)
        ->assertCanSeeTableRecords([$hidden])
        ->assertCanNotSeeTableRecords([declaredAbility()])
        ->assertOk();
});

test('composing offers the actions and the models the panel declares', function (): void {
    livewire(CreateAbility::class)
        ->assertSchemaComponentExists(AbilityForm::NAME_PICKED, checkComponentUsing: function (Select $field): bool {
            $options = $field->getOptions();

            return array_key_exists('view', $options) && array_key_exists('*', $options);
        })
        ->assertSchemaComponentExists('entity_type', checkComponentUsing: function (Select $field): bool {
            $options = $field->getOptions();

            return array_key_exists(Post::class, $options) && array_key_exists('*', $options);
        });
});

test('composing lets the name out of the list, which is the one thing this screen is for', function (): void {
    livewire(CreateAbility::class)
        ->callAction(TestAction::make('typeName')->schemaComponent(AbilityForm::NAME_PICKED))
        ->assertSchemaStateSet([AbilityForm::NAME_CUSTOM => true])
        ->fillForm(['name' => 'archive'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Models::ability()->newQuery()->where('name', 'archive')->exists())->toBeTrue();
});

test('composing takes the name back to the list when asked', function (): void {
    livewire(CreateAbility::class)
        ->callAction(TestAction::make('typeName')->schemaComponent(AbilityForm::NAME_PICKED))
        ->callAction(TestAction::make('pickName')->schemaComponent(AbilityForm::NAME_TYPED))
        ->assertSchemaStateSet([AbilityForm::NAME_CUSTOM => false]);
});

test('composing writes every column the store holds', function (): void {
    $post = Post::forceCreate(['title' => 'Kept']);

    livewire(CreateAbility::class)
        ->fillForm([
            AbilityForm::NAME_CUSTOM => true,
            'name' => 'archive',
            DerivedTitle::CUSTOM => true,
            'title' => 'Archive posts',
            'entity_type' => Post::class,
            'entity_id' => $post->getKey(),
            'only_owned' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $rule = Models::ability()->newQuery()->where('name', 'archive')->sole();

    expect($rule->getAttribute('title'))->toBe('Archive posts')
        ->and($rule->getAttribute('entity_type'))->toBe(Post::class)
        ->and($rule->getAttribute('only_owned'))->toBeTrue();
});

test('composing refuses a rule the store already holds down to the last column', function (): void {
    stored(['entity_type' => Post::class]);

    livewire(CreateAbility::class)
        ->fillForm([
            AbilityForm::NAME_CUSTOM => true,
            'name' => 'archive',
            'entity_type' => Post::class,
        ])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

test('composing allows a rule differing only in the column that fences it', function (): void {
    stored(['entity_type' => Post::class]);

    livewire(CreateAbility::class)
        ->fillForm([
            AbilityForm::NAME_CUSTOM => true,
            'name' => 'archive',
            'entity_type' => Post::class,
            'only_owned' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

test('composing names the record a rule is being fenced to, and says so when it is not there', function (): void {
    $post = Post::forceCreate(['title' => 'Amaru']);

    livewire(CreateAbility::class)
        ->fillForm([AbilityForm::NAME_CUSTOM => true, 'name' => 'archive', 'entity_type' => Post::class, 'entity_id' => $post->getKey()])
        ->assertSchemaComponentExists('entity_id', checkComponentUsing: fn (TextInput $field): bool => $field->getHint() !== null)
        ->fillForm(['entity_id' => 9999])
        ->assertSchemaComponentExists('entity_id', checkComponentUsing: fn (TextInput $field): bool => $field->getHint() === __('filament-bouncer::abilities.form.record_missing')
            && $field->getHintColor() === 'danger');
});

test('composing titles the record through the panel resource that knows how to title it', function (): void {
    $role = Models::role()->newQuery()->create(['name' => 'support', 'title' => 'Support desk']);

    // Fenced to a model the panel actually puts on screen, which is what makes the resource — and
    // not the bare id — the one that names the row.
    livewire(CreateAbility::class)
        ->fillForm([
            AbilityForm::NAME_CUSTOM => true,
            'name' => 'archive',
            'entity_type' => $role->getMorphClass(),
            'entity_id' => $role->getKey(),
        ])
        ->assertSchemaComponentExists('entity_id', checkComponentUsing: fn (TextInput $field): bool => $field->getHint() === 'support');
});

test('composing falls back to the bare key where the panel shows no resource for that model', function (): void {
    // The account is the fixture model no resource of the panel puts on screen, which is what leaves
    // the hint with nobody to ask for a title.
    $account = User::forceCreate(['name' => 'Amaru', 'email' => 'amaru@example.test', 'password' => 'x']);

    livewire(CreateAbility::class)
        ->fillForm([
            AbilityForm::NAME_CUSTOM => true,
            'name' => 'archive',
            'entity_type' => $account->getMorphClass(),
            'entity_id' => keyOf($account),
        ])
        ->assertSchemaComponentExists('entity_id', checkComponentUsing: fn (TextInput $field): bool => $field->getHint() === '#'.keyOf($account));
});

test('fencing one like this opens the form with the rule already in it', function (): void {
    $origin = stored(['entity_type' => Post::class, 'only_owned' => true]);

    livewire(ListAbilities::class)
        ->searchTable('archive')
        ->assertActionHasUrl(
            TestAction::make('narrow')->table($origin),
            AbilityResource::getUrl('create', [
                'name' => 'archive',
                'entity_type' => Post::class,
                'only_owned' => '1',
            ]),
        );
});

test('changing a rule rewrites every column', function (): void {
    $rule = stored();

    livewire(EditAbility::class, ['record' => $rule->getRouteKey()])
        ->fillForm([
            AbilityForm::NAME_CUSTOM => true,
            'name' => 'unarchive',
            'entity_type' => Post::class,
            'only_owned' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $rule = Models::ability()->newQuery()->whereKey($rule->getKey())->sole();

    expect($rule->getAttribute('name'))->toBe('unarchive')
        ->and($rule->getAttribute('entity_type'))->toBe(Post::class)
        ->and($rule->getAttribute('only_owned'))->toBeTrue();
});

test('changing a rule does not call it its own twin', function (): void {
    $rule = stored();

    livewire(EditAbility::class, ['record' => $rule->getRouteKey()])
        ->fillForm([AbilityForm::NAME_CUSTOM => true, 'name' => 'archive'])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('the record page reads the rule and asks the four questions about it', function (): void {
    $rule = declaredAbility();

    livewire(ViewAbility::class, ['record' => $rule->getRouteKey()])
        ->assertSee(Ailment::Twin->question())
        ->assertSee(Ailment::GhostModel->question())
        ->assertOk();
});

test('the record page accounts for how a row goes, and says nothing over one that goes nowhere', function (): void {
    // Fenced to one record, which is a shape the reconciliation never writes and therefore never
    // speaks for — the one state with no account to give, against a declared row, which has one.
    $apart = stored(['entity_type' => Post::class, 'entity_id' => Post::forceCreate(['title' => 'Fenced'])->getKey()]);

    expect(Declaration::of($apart))->toBe(Declaration::Apart)
        ->and(Declaration::of($apart)->note())->toBeNull()
        ->and(Declaration::of(declaredAbility())->note())->not->toBeNull();
});

test('the probe offers the roles beside the accounts, and asks about a rule with no model', function (): void {
    $loose = stored(['name' => 'export']);
    Models::role()->newQuery()->create(['name' => 'support']);

    // A rule about no model is asked with no subject at all, which is the one shape the Gate
    // answers without anything to point at.
    $role = Models::role()->newQuery()->where('name', 'support')->sole();

    expect(ProbeAbility::isAskable($loose))->toBeTrue()
        ->and(ProbeAbility::answers($loose, 'role:'.keyOf($role), null))->toBeFalse();

    grant($role, [['export', null]]);

    expect(ProbeAbility::answers($loose, 'role:'.keyOf($role), null))->toBeTrue();

    livewire(ViewAbility::class, ['record' => $loose->getRouteKey()])
        ->mountAction(TestAction::make('probe'))
        ->assertSchemaComponentExists(ProbeAbility::HOLDER, checkComponentUsing: fn (Select $field): bool => array_key_exists(__('filament-bouncer::abilities.probe.roles'), $field->getOptions()));
});

test('the probe answers about a role and about an account', function (): void {
    $rule = declaredAbility();
    $role = Models::role()->newQuery()->create(['name' => 'support']);

    expect(ProbeAbility::answers($rule, null, null))->toBeNull()
        ->and(ProbeAbility::answers($rule, 'role:'.keyOf($role), null))->toBeFalse();

    grant($role, [['view', Post::class]]);

    expect(ProbeAbility::answers($rule, 'role:'.keyOf($role), null))->toBeTrue();
});

test('the probe refuses to ask about a rule naming a model that cannot be loaded', function (): void {
    $ghost = stored(['entity_type' => 'App\\Models\\Gone']);
    $role = Models::role()->newQuery()->create(['name' => 'support']);

    expect(ProbeAbility::isAskable($ghost))->toBeFalse()
        ->and(ProbeAbility::answers($ghost, 'role:'.keyOf($role), null))->toBeNull();
});

test('the probe says nothing about a holder that is not there', function (): void {
    expect(ProbeAbility::answers(declaredAbility(), 'role:9999', null))->toBeNull()
        ->and(ProbeAbility::answers(declaredAbility(), 'nocolon', null))->toBeNull();
});

test('the probe reads and paints the three things a verdict can be', function (): void {
    expect(ProbeAbility::reading(true))->not->toBeEmpty()
        ->and(ProbeAbility::reading(false))->not->toBeEmpty()
        ->and(ProbeAbility::reading(null))->not->toBeEmpty()
        ->and(ProbeAbility::tone(true))->toBe('success')
        ->and(ProbeAbility::tone(false))->toBe('danger')
        ->and(ProbeAbility::tone(null))->toBe('gray');
});

test('the probe is offered on both record pages and writes nothing', function (): void {
    $rule = declaredAbility();

    livewire(ViewAbility::class, ['record' => $rule->getRouteKey()])
        ->mountAction(TestAction::make('probe'))
        ->assertActionMounted(TestAction::make('probe'));

    livewire(EditAbility::class, ['record' => $rule->getRouteKey()])
        ->assertActionExists('probe');
});

test('the probe hides the record field on a rule with no model to fence', function (): void {
    $loose = stored(['name' => 'export']);

    livewire(ViewAbility::class, ['record' => $loose->getRouteKey()])
        ->mountAction(TestAction::make('probe'))
        ->assertSchemaComponentExists(ProbeAbility::RECORD, checkComponentUsing: fn (TextInput $field): bool => $field->isHidden());
});

test('the probe says so instead of asking when the model cannot be loaded', function (): void {
    $ghost = stored(['entity_type' => 'App\\Models\\Gone']);

    livewire(ViewAbility::class, ['record' => $ghost->getRouteKey()])
        ->mountAction(TestAction::make('probe'))
        ->assertSchemaComponentDoesNotExist(ProbeAbility::HOLDER);
});

test('the row that makes the privileged role privileged is not worked on from here', function (): void {
    // The very pair `PrivilegedRole` asks the clipboard about, and writes back to restore that
    // role's reach: renaming it or pointing it at a model takes the reach away without the role
    // being touched.
    $wildcard = stored(['name' => AbilityStore::WILDCARD, 'entity_type' => AbilityStore::WILDCARD]);

    expect(AbilityResource::canEdit($wildcard))->toBeFalse()
        ->and(AbilityResource::isLocked($wildcard))->toBeTrue()
        ->and(AbilityResource::canEdit(declaredAbility()))->toBeTrue()
        ->and(AbilityResource::isLocked(declaredAbility()))->toBeFalse();

    livewire(ListAbilities::class)
        ->searchTable(AbilityStore::WILDCARD)
        ->assertActionVisible(TestAction::make('locked')->table($wildcard))
        ->assertActionHidden(TestAction::make('edit')->table($wildcard));
});

test('a rule reaching every model of one action is still worked on from here', function (): void {
    // The wildcard on one half only: it grants a great deal, but nothing depends on it to get
    // back in, so locking it would be locking a row this screen exists to correct.
    $sweep = stored(['name' => 'sweep', 'entity_type' => AbilityStore::WILDCARD]);

    expect(AbilityResource::canEdit($sweep))->toBeTrue()
        ->and(AbilityResource::isLocked($sweep))->toBeFalse();
});
