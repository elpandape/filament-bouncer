# Filament Bouncer

Roles and abilities for [Filament](https://filamentphp.com), built on
[silber/bouncer](https://github.com/JosephSilber/bouncer).

> **Stable from `1.0.0`.** Semantic versioning from here: breaking the public API takes a
> major bump. That promise covers the **names abilities are stored under** as well as the
> classes — those names are rows in your database, and changing how one is spelled would
> silently drop every grant that pointed at it.

## Why Bouncer

There is already an excellent package for roles and permissions on Filament built on
`spatie/laravel-permission`, and for most projects that is the right answer. This one exists
for the two things Bouncer does that spatie does not:

- **Explicit denials.** `forbid()` beats an allow, so "everything except deleting for good"
  is expressed as the exception it is, and survives the catalogue growing later.
- **Per-model and per-instance abilities.** "This editor only touches their own posts" is
  something Bouncer can store; spatie cannot.

If you need neither, you probably do not need this package.

## What this does that a permissions screen usually does not

A wall of checkboxes is the easy part. These are the parts that are not:

- **The abilities are derived from code, and only from code.** A resource offers exactly
  the actions its policy declares — there is no fixed list of actions this package
  invented. Delete a policy method and its switch disappears; add one and it appears. An
  ability that nothing ever consults cannot exist, which matters because Bouncer answers
  a name nobody created without ever complaining.
- **Abilities are stored against models, not as strings.** `view` on `App\Models\Post`,
  not `view_any_post`. Renaming a resource orphans nothing, two models with the same
  basename in different namespaces do not collide, and grants on a single record remain
  possible later without migrating anything already stored.
- **A rule can be narrowed from the screen.** "This editor, but only their own posts" is
  something Bouncer stores in columns a policy method has no way to name, so the abilities
  screen composes it: the same rule, held down to what its holder owns or to one record.
- **The panel refuses to boot** with a page or a widget that authorises nobody.
- **A build goes red** when the store has drifted from the catalogue, or when a resource
  has no policy and is therefore open to everybody.
- **There is a way back in.** Handing out abilities is itself an ability, so it can be
  handed away; the privileged role is put back on every reconcile.
- **A denial is a state, not an absence**, and it beats a grant from anywhere else.

Between them those form a loop: the code declares, the catalogue derives, the store is
reconciled, and the guard and the check refuse to let any of the three drift apart.

## Requirements

- PHP 8.5
- Laravel 13
- Filament 5.7
- silber/bouncer 1.0.4

## Installation

```bash
composer require elpandape/filament-bouncer
```

The service provider is registered through package discovery. Register the plugin on the
panel that should carry the roles screen:

```php
use ElPandaPe\FilamentBouncer\Filament\FilamentBouncerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentBouncerPlugin::make());
}
```

Publish the configuration if you want to change how the package presents itself in the
panel:

```bash
php artisan vendor:publish --tag=filament-bouncer-config
```

### Bouncer's own tables

Bouncer does not load its migrations automatically and ships no install command. Publish and
run them yourself:

```bash
php artisan vendor:publish --tag=bouncer.migrations
php artisan migrate
```

**If the application already uses `spatie/laravel-permission`, that migration will fail.**
Bouncer creates `abilities`, `roles`, `assigned_roles` and `permissions`, and the middle two
collide with spatie's. Rename Bouncer's side before migrating — spatie's tables are usually
the ones already in production, and an executed migration should not be edited:

```php
Bouncer::tables([
    'roles' => 'bouncer_roles',
    'permissions' => 'bouncer_permissions',
]);
```

The rename has to happen before the migration runs, so it belongs in a service provider that
registers early.

## The catalogue

The catalogue is the list of abilities your panel is able to ask about. It is derived from
your code on every build and never read back from the store, so an ability that nothing
consults cannot survive in it.

| Where it comes from | What it contributes |
|---|---|
| A resource | One ability per method its model's policy declares, stored against that model |
| A model listed in `models` | The same, for a model that has a policy but no resource |
| A page | One ability, `page:<class>`, standing for reaching it at all |
| A widget | One ability, `widget:<class>`, standing for seeing it at all |
| An entry in `custom` | One ability under exactly the name you gave it |

**A model with no policy contributes nothing, on purpose.** Its abilities would be ones no
code ever consults, and a switch that decides nothing is worse than no switch at all. If a
resource is missing from the grid, the answer is to write its policy.

Every action is sorted into one of four scopes — `read`, `write`, `withdraw`,
`irreversible` — which is what lets a screen stop "see a list" from looking like the same
decision as "delete for good". Anything the `scopes` map does not name counts as a write.

## Keeping the store in step

```bash
php artisan filament-bouncer:reconcile
```

Creates every ability the catalogue declares and the store is missing, in a single insert,
and clears Bouncer's cache afterwards. Run it after `migrate` on every deploy.

| Option | What it does |
|---|---|
| `--panel=` | Walk a named panel instead of the default one |
| `--prune` | Delete stored abilities the catalogue no longer declares |
| `--check` | Write nothing, report the differences, and exit non-zero if there are any |

Without `--prune` an undeclared ability is reported and left alone, because deleting an
ability takes every grant that pointed at it with it. `--check` is the shape for continuous
integration: it fails a build whose catalogue and store have drifted apart.

Three kinds of row are never touched, in either direction: abilities about one record,
abilities restricted to what their holder owns, and the wildcards that a blanket grant such
as `everything()` leaves behind. The catalogue does not declare them, so it does not get to
delete them either — which is exactly why the abilities screen is allowed to make the first
two of them.

## The abilities screen

The other axis of the same table: not what a role may do, but what may be done. It is the store's
workbench — the roles screen is for handing abilities out, this one is for keeping the store sound.

Each row carries the name the code asks the Gate, its title, **how far it reaches**, where it stands
with the reconciliation, and its **health**.

### Composing a rule

Both the action and the model are picked from the catalogue rather than typed: the actions are the
methods of your policies, the models are what the panel puts on screen. The reading leads and the
identifier follows — `Update (update)` — because the reading is what the list is scanned by and the
identifier is what gets stored.

The **name can be taken out of the list**, with a button beside it, because composing a rule the
catalogue does not yet declare is the one thing this screen is for. **The model cannot**, and that
asymmetry is deliberate: a model the panel does not declare is exactly what the health column reports
as a ghost, and typing one by hand would be composing what the other half of the screen exists to
detect.

The title composes itself while you type, with Bouncer's own generator, and stays closed until you
ask for it — a derived title and a hand-written one read identically on screen, so without the lock
there is no telling which one is in front of you and no way back to the derived one.

Two things are refused, at the write and not only on the screen: a **second row saying what one
already says**, because the two are granted and withdrawn separately and whoever withdrew one would
believe the rule was gone; and a **record that is not there**, which is said in red beside the field
as it is typed rather than after the save.

| Narrowed to | What Bouncer stores | What it means |
|---|---|---|
| What its holder owns | `only_owned = true` | The rule holds for the records that belong to whoever has it |
| One record | `entity_id = 7` | The rule holds for that record and no other |

### Health

Four things that are true in the store, that nothing else detects, and that can only be mended from
here — because this is the only screen that lists every row.

| | What it means |
|---|---|
| **Duplicate** | Another row says exactly the same thing. They are granted and withdrawn separately |
| **Ghost model** | The model it names no longer loads. Bouncer answers no, for ever, and never says why |
| **Ghost record** | It is fenced to a record that has been deleted |
| **Invisible** | It carries a tenant on an installation that does not use one, so nothing else can see it |

None of them is the reconciliation's business: a row the catalogue no longer declares is *healthy* —
`--prune` takes it and says so — and the declaration column already reports that. Conflating the two
teaches people to ignore both.

The listing sums a row up in **one icon** — sound, out of sight, or answering wrongly — with the
whole account in its tooltip and a filter to see one ailment at a time. The record page asks the four
questions and answers each **with the fact inside**: which row is the twin, which class does not
load. "Duplicate" without saying which sends you off to look for it.

### Trying a rule out

That a role holds a rule and that the Gate says yes to it are not the same thing: a denial from
another role wins, the wildcard grants what nobody wrote down, and a fenced rule only answers about
the record it fences. The record page will ask Bouncer, for any role or account, what it answers
right now — which beats the alternative of handing the rule out and watching.

### The wildcard row is not worked on from here

`*` over `*` is what makes the role that holds everything hold it: the package asks Bouncer's
clipboard for exactly that pair to know whether the role still reaches everything, and writes
that very row back to restore it. Renaming it, or pointing it at a model, takes that role's
reach away without the role being touched — so the row carries a padlock instead of a way of
editing it, the same as the privileged role on the other screen. Only that pair: `*` over one
model grants a great deal, but nothing depends on it to get back in.

### There is no delete, here or anywhere on this screen

A row is pointed at by every grant ever made from it, and taking the row away takes all of them with
it, silently. The way a row goes is that the code stops declaring it and `--prune` sweeps it,
reporting how many it swept — which is what the declaration column is for.

### The health widget

`ElPandaPe\FilamentBouncer\Filament\Widgets\AbilityHealth` counts each ailment across the whole
table, from outside the screen, with every figure opening its rows. **The plugin does not register
it**, on purpose: doing so would put it on your dashboard uninvited and — because a panel refuses to
boot with a widget that declares nobody — add an entity to your catalogue, turning `--check` red
until you reconcile. Add it yourself when you want it:

```php
use ElPandaPe\FilamentBouncer\Filament\Widgets\AbilityHealth;

$panel->widgets([
    AbilityHealth::class,
]);
```

Then run `filament-bouncer:reconcile` so the ability it declares is written down, and grant it like
any other.

## The roles screen

The list answers what a list of names cannot: how far each role reaches. Every row draws
the catalogue to scale — a bar of what the role grants, what it forbids and what it leaves
alone — beside the accounts that hold it. A role reaching everything through the wildcard
holds no rule of its own for any cell, so a bar drawn from its grants alone would say it
can do nothing at all; it is drawn full instead, and says why in words. Above the table
stand four figures — the roles, the abilities the panel declares, the denials in force,
and the accounts that reach the panel holding no role at all — and the last two are there
precisely because no row below can show them: a denial explains a role that looks generous
and is not, and an account holding nothing reaches the panel and finds it empty, which is
a support ticket waiting to be filed.

Composing a role takes three steps — the name, the abilities, and one plain sentence of
what is about to be written — because handing out abilities deserves reading once before
it is done.

The catalogue is laid out as a section per entity and a row per action, and each row
carries one control with three positions, chosen rather than cycled to: with a single cell
that walked the three states, three buttons never fitted a column — so the stance was a
shape the reader decoded — and reaching a denial meant passing through a grant, a rule
that existed on screen for a moment and never on purpose. Beside each action sits the mark
of its weight — read, write, withdraw, irreversible — which is what keeps "see a list" and
"delete for good" from looking like the same decision. Pages, widgets and custom abilities
answer exactly one question each, so they read as flat lists with the same control.

An entity's own heading carries the model it decides about — not the policy that answers
for it, which is a fact about the code the catalogue already holds — an icon if `icons`
names one for that model, how many of its actions are granted, how many it forbids, and
the three presets folded behind a single control. The denial count is red and counted in
the browser, so it moves while somebody is still deciding rather than after the save.

Each row holds one of three stances, and the middle one is not a quieter way of saying no:

| Stance | What the role is saying |
|---|---|
| **Granted** | This ability is given |
| **Not granted** | This role says nothing; the answer comes from whatever else the person holds |
| **Forbidden** | Nobody holding this role has it, whatever else grants it |

A denial beats a grant from another role and one made straight to the user. That is the one
thing Bouncer can express and `spatie/laravel-permission` cannot, and it is why "everything
except deleting for good" survives the catalogue growing later instead of quietly picking up
whatever gets added.

A catalogue of sixty rows or fewer opens whole, because below that the fold buys nothing
and costs a click on every entity somebody came to change — and a screen that opens
showing only headings reads as broken. Past sixty it arrives folded, because three buttons
a row would otherwise draw five hundred at once. Each entity offers three shortcuts —
reading, everything, nothing — and only reading carries a list of its own: a shortcut for
withdrawing or for the irreversible is a shortcut nobody should have. A summary at the
foot counts what the role adds up to — granted, forbidden, not granted — while it is being
changed, not after it is saved.

Beside a stance goes what the stance cannot say, each of them a way for a grid read at
face value to be wrong about the role in front of it: reached through a broader rule,
where the neutral position draws a hollow tick — the answer is yes, and the rule behind it
is not this row's; granted here and still refused, because a denial beats it from
elsewhere; or narrowed to one record or to what the holder owns, by rules this grid
neither writes nor removes. The record page reads a role in the same shape it is written in — the same grid,
disabled, under the reach bar at full size — so that learning where a cell is on one
screen is learning it on both.

**Forbidding is offered on exactly the abilities granting is offered on, and no others.** It
would be arguable that restricting is a smaller power than granting, and the decision here
went the other way: a denial you cannot lift afterwards is a way to lock people out of
something you were never trusted with, so both go through the same gate.

Whoever the policy lets onto this screen hands out **everything the panel declares**,
whether or not they hold it themselves — including the wildcard, and including to
themselves. That is a deliberate choice, and the same one `yadahan/nova-bouncer` makes:
being trusted to edit roles is the whole of the trust. If that is more than you want to
give somebody, the answer is not to let them onto the screen.

Three things it still refuses, and none is only a hidden button — each is checked again
where the write happens, so a request built by hand meets the same refusal:

- **Nobody edits a role they hold themselves.** Otherwise raising your own reach is one save
  away.
- **Nobody edits the role that holds everything.** It is the way back in, and a way back in
  that can be edited is not one.
- **Nobody names a role after it either.** Left alone, the refusal above was a way to take
  the name hostage: a role created or renamed to it granted nobody anything and could never
  again be edited or deleted from here. The reconciliation is what writes that role, so
  this screen has no business writing its name.

### The way back in

Handing out abilities is itself an ability, so a panel can be talked into a state where
nobody left is able to hand anything out. Name a role in `privileged_role` and
`filament-bouncer:reconcile` makes sure it exists and holds Bouncer's wildcard — creating the
role and granting the wildcard back whenever either is missing, including after somebody
deletes the role or strips it of the wildcard by hand, but never rewriting a role that already
holds it. `--check` fails while it is missing.

A role nobody holds opens no doors, so there is a command for the last step too:

```bash
php artisan filament-bouncer:assign owner amaru@example.com
```

It takes a key or an email address, and it **refuses a role that does not exist** rather
than creating one. That refusal is the point: `Bouncer::assign()` creates a role it cannot
find, so a misspelling otherwise leaves somebody holding a brand new role that grants
nothing at all, under a line of output saying it worked.

The wildcard is granted rather than every ability the catalogue holds today, so that a
resource added tomorrow is covered without anybody remembering to come back. That the
wildcard also grants abilities nobody ever declared is exactly what is wanted for this role,
and for no other.

## Closing the panel

Filament decides what a reader may do by asking a policy, and **when there is no policy it
asks nobody and lets everybody through**. Three things close that, and none of them is
optional if you want the panel actually shut.

### Policies

```bash
php artisan filament-bouncer:policy
```

Writes a policy for every resource of the panel whose model has none, leaving alone
anything already there unless you pass `--force`. Name models on the command line to write
for something that has no resource. Publish the stub with
`--tag=filament-bouncer-stubs` to write them in your own house style.

The generated methods are the declaration: the catalogue reads them straight back, so what
an administrator is offered for a model is exactly what its policy is prepared to answer.

Each one extends `ElPandaPe\FilamentBouncer\Policies\BouncerPolicy`, which carries no actions
of its own — a base quietly supplying twelve of them would offer restoring and force deleting
for a model that has neither — and one `allows()` that asks Bouncer's clipboard rather than
the Gate, since going through the Gate would resolve that very policy and ask it the same
question forever.
Delete a method you do not want and its row goes with it.

The roles screen is governed the same way, by a policy this package registers for the role
model. Nothing about it is special-cased. Register your own policy for that model from a
provider of your own if you want a different answer — yours boots afterwards and wins.

### Pages and widgets

They have no policy to ask, so they decide for themselves:

```php
use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesPage;

class Reports extends Page
{
    use AuthorizesPage;
}
```

`AuthorizesWidget` does the same for a widget. Writing `canAccess()` or `canView()` by hand
satisfies the guard just as well — what it objects to is neither.

### The guard

A panel carrying a page or a widget that authorises nobody does not boot. It throws in
production too, and that is the decision rather than an oversight: such a component looks
exactly like one that was meant to be open, so nothing about the screen gives it away. A
deployment that falls over loudly gets reverted within the hour; a hole of this kind is
found by whoever goes looking, and they are not on your side.

The way to say "this one really is for everybody" is the `ignore` list, which is that
decision written down where the next reader will find it. A component named there is also
left out of the catalogue, so there is no ability to grant and none to withhold.

### The check

```bash
php artisan filament-bouncer:reconcile --check
```

Fails on any of three things: an ability the catalogue declares and the store lacks, an
ability the store holds and the catalogue no longer declares, or a resource whose model has
no policy at all. Put it in continuous integration and the panel cannot drift away from its
own authorisation without a build going red.

## The words on the screen

Three sources are asked, in this order:

1. **What you put in `labels`**, because you know what your own people call these things
   and should not have to publish a language file to say so.
2. **The package's translations.** English and Spanish ship with it; publish them with
   `--tag=filament-bouncer-translations` to add or change a language.
3. **The method name, made readable.** An action your own policy invented shows up reading
   sensibly with nothing translated first.

```php
'labels' => [
    'actions' => ['viewAny' => 'Browse'],
],
```

Everything a person reads — in the panel and in the console — goes through that chain.
Exception messages do not: they are for whoever is reading a stack trace, and a translated
one cannot be searched for.

The title Bouncer stores alongside each ability follows the locale in force when
`filament-bouncer:reconcile` ran, since that is when the column is written.

## Configuration

| Key | What it decides |
|---|---|
| `panel` | The panel whose components declare the catalogue. `null` uses the default one |
| `tenancy` | Whether the tenant is part of the screens' vocabulary at all. `null` asks the panel |
| `navigation.roles.icon` | The icon of the roles resource. `null` leaves it without one |
| `navigation.roles.group` | The navigation group it belongs to, and the one abilities take too. `null` leaves both ungrouped |
| `navigation.roles.sort` | Its position. `null` leaves Filament's own ordering |
| `navigation.roles.slug` | The path under the panel. Defaults to `security/roles` |
| `navigation.abilities.icon` · `.sort` · `.slug` | The same three decisions for the abilities screen. Defaults to `security/abilities` |
| `relation.icon` | The icon on the roles tab of an account's page. Filament draws that tab from the second relation manager on |
| `scopes` | Which actions count as reading, withdrawing and irreversible |
| `models` | Models with a policy but no resource, which would otherwise never be reached |
| `custom` | Abilities no component declares, as a map of name to scope |
| `ignore` | Resources, pages and widgets the catalogue leaves out |
| `privileged_role` | The role that holds everything, and that the screen refuses to edit |
| `ownership` | Which column says a record belongs to somebody, model by model |
| `labels` | Your own words for the actions, scopes and stances |
| `icons` | An icon for an entity on the roles screen, by model class. Anything unnamed is drawn without one |

The navigation keys are presentation decisions that belong to the application, not to the
package, which is why they are read from configuration rather than from a static property.

`tenancy` decides whether Bouncer's tenant column is drawn at all — the column on both
listings, the section on both record pages, the entry on both forms — and whether a row
carrying one is reported as hidden from the rest of the system. Left `null` it asks the panel
whether Filament's own tenancy is turned on, so an installation that does not scope its rows
needs to set nothing and is never shown a column every row answers the same way. Set it to
`true` where you scope Bouncer's rows by something Filament does not know about.

### Ownership

Bouncer asks who owns a record on **every check it answers about one**, whether or not a
single ability was ever held down to what its holder owns. Told nothing about a model it
guesses a column named after whoever is asking — `user_id` — and reads it through the
model. Under `Model::shouldBeStrict()` that read throws, from inside a view, naming a
column nobody ever wrote.

So this package answers for every model: nothing is owned unless you say otherwise.

```php
'ownership' => [
    \App\Models\Post::class => 'author_id',
],
```

A named model reads its column out of the attributes the record actually carries, so one
loaded without it answers no rather than throwing. Column names, never closures — this
file is cached, and `config:cache` throws on anything it cannot serialise.

> [!WARNING]
> An application that relied on Bouncer's `user_id` guess loses it here, silently and
> towards denying. Name the models whose records have owners.

## Events

Every write this package makes is announced. Nothing is dispatched by Bouncer itself, so
**a `Bouncer::allow()` your application calls on its own fires nothing** — a listener that
invalidates a cache must not read "no event" as "no change".

| Event | When |
|---|---|
| `RoleAssignedEvent` | A role was handed out, from a form, from the roles tab or from `filament-bouncer:assign` |
| `RoleRetractedEvent` | A role was taken away from the roles tab |
| `RoleDeletedEvent` | A role was deleted, taking its holders' assignments and all its stances with it |
| `AbilityStanceChangedEvent` | What a role says about one rule changed, in either direction |
| `PrivilegedRoleRestoredEvent` | The role that holds everything was created or handed the wildcard back |
| `CatalogReconciledEvent` | `filament-bouncer:reconcile` finished, with what it wrote and what it took away |

`filament-bouncer:reconcile --check` writes nothing, so it announces nothing either — neither
this event nor `PrivilegedRoleRestoredEvent` fires. `CatalogReconciledEvent` itself fires at the
end of every real run, including one that wrote nothing and pruned nothing: it reports that the
command ran, not that anything changed. That is the opposite of `AbilityStanceChangedEvent`,
which stays quiet when a cell is saved holding the stance it already had — worth saying once, or
the contrast reads as a bug rather than as two different questions answered two different ways.

Each carries `?Model $causer` — whoever was signed in, and `null` from a command, which is the
truth about a break-glass path rather than a missing value.

**Every write refreshes Bouncer's clipboard before its event goes out.** A listener may ask the
Gate from inside its own handler and get the answer that is true after the write, not the one
that was true a moment before it. This took more than one fix round to hold everywhere, and a test
holds it on every path that announces something: assigning a role from the account's create
screen, from the roles tab, or from the command; retracting from the tab; deleting a role;
`RoleAbilities::saveRow()`; the abilities grid's own save, which holds every changed cell's event
back until the whole form is written and the clipboard is refreshed once, rather than firing cell
by cell against a grid only half applied; restoring the role that holds everything; and
reconciling the catalogue.

Here is a listener that turns an assignment into an audit entry, using
[`spatie/laravel-activitylog`](https://spatie.be/docs/laravel-activitylog)'s `activity()`
helper — it is **not** a dependency of this package, so install it yourself if you want this
exact shape:

```php
Event::listen(function (RoleAssignedEvent $event): void {
    activity()
        ->causedBy($event->causer)
        ->performedOn($event->authority)
        ->event('assigned the role')
        ->log($event->role);
});
```

The ability events carry an `AbilityRef`, which names the rule the way the store spells it:
`name`, `entityMorphClass`, `entityId`, `onlyOwned`, `scope` and `title`, plus `identity()` and
`describe()`. A listener that needs the model class resolves it with
`Relation::getMorphedModel($ref->entityMorphClass)`.

`RoleAbilities::saveRow()` also emits. Nothing inside this package calls it — it is store API for
a consumer writing a single stored row.

## What this deliberately does not do

Each of these was considered and turned down. They are written here so that the next
person to want one finds the reasoning rather than the silence.

- **Deleting an ability from a screen.** A row goes away when the reconciliation stops
  declaring it, and `--prune` says how many it took. Offering a button would be offering
  to take every grant pointing at the row with it, on one click and no second question.
  This one is armed, not just drawn: tests drive a delete by hand through every door a
  request could reach one by, and assert the row still standing.
- **Pointing a narrowed rule at one record of something that is not a model.** A page or
  a widget is reached or it is not: there is no record to point at, so the composer
  refuses that reach for them.
- **Bouncer's Constraints.** They are persisted and never evaluated: an ability with an
  impossible constraint still passes. Offering them would be writing decorative JSON.
  Making them real means a clipboard of our own, which is a component and not an adapter.
- **Bulk actions, on either table.** Filament authorises a bulk delete once for the whole
  selection, and the two refusals that keep the privileged role and your own role out of
  reach live on the resource — a bulk delete would walk past both. The abilities table
  offers a selection nothing for the same reason it offers no delete at all.
- **Bouncer's multi-tenant scope.** The reads this package makes pass the configured scope
  through, but nothing here is tested against a scoped installation, and
  `assigned_roles.restricted_to_id` is a dead column in Bouncer 1.0.4 whatever the schema
  suggests.
- **A transaction around the abilities grid's save.** Its cells are written one at a time and
  the events they raise are collected as they go, dispatched together only once the whole form
  is written and the clipboard is refreshed. If a later cell throws, the earlier writes in that
  same save stay in the database — Bouncer never rolls anything back — but the events already
  collected for them are thrown away with the request, so a listener writing an audit trail
  never hears about grants that are sitting in the store regardless. The honest fix is wrapping
  the save in a transaction and dispatching after the commit, which is a design decision this
  package has not made yet.

## Things about Bouncer worth knowing before you build on it

All of these were measured by running its code, not read from documentation. They are not
defects of this package, but they shape what it can honestly offer.

- **Bouncer always hooks the Gate and there is no way to switch that off.** Both the `before`
  and the `after` closure are registered unconditionally; which one answers is decided by a
  slot that **defaults to `after`**, so your policies decide first and Bouncer only speaks when
  none of them did.
- **The cache is not invalidated by writes.** Check an ability, grant it, check again, and the
  second check still returns the old answer until `Bouncer::refreshFor($user)`. Within a single
  Livewire request — which is how Filament works — a write followed by a read already lies.
- **The Constraints subsystem is persisted but never evaluated.** An ability with an impossible
  constraint still passes `Gate::allows()`.
- **The wildcard grants abilities that do not exist.** With `allow($user)->everything()`,
  `Gate::allows('a-name-nobody-ever-created')` returns `true`. There is no equivalent of
  spatie's `PermissionDoesNotExist`, so a typo in a policy fails silently rather than loudly.
- **`assigned_roles.restricted_to_id` / `restricted_to_type` are dead columns** in 1.0.4: they
  are created and nothing in the source ever reads or writes them. There is no per-tenant role
  restriction implemented.
- **Ownership is guessed from a column that often does not exist, and under
  `Model::shouldBeStrict()` the guess throws.** Bouncer asks whether the authority owns the
  record on every check it answers, and with nothing configured it looks for a column named
  after whoever is asking — `user_id`. Ask about a record that has no such column and a lax
  application gets null, while a strict one gets a `MissingAttributeException` naming a
  column nobody ever wrote, from inside a Blade view. This package tells Bouncer that nobody
  owns a role, which covers its own screen; for your own models, say so once:

  ```php
  Bouncer::ownedVia('*', fn (Model $model, Model $authority): bool => array_key_exists('user_id', $model->getAttributes())
      && $model->getAttribute('user_id') === $authority->getKey());
  ```

- **`ownedVia()` cannot be called with one argument.** The single-argument form is documented
  as the way to set a global rule, and it assigns the closure to `$ownership['*']` and then
  immediately uses that same closure as an array key, which is a `TypeError`. Pass `'*'`
  explicitly as the first argument instead, as above.

## Testing

The package carries its own toolchain and does not rely on any consuming application:

```bash
composer test:all
```

That runs, in order: code style, line coverage, type coverage, profanity, static analysis at
its maximum level, and a refactoring dry run. All of them are thresholds, not diagnostics.

If the type coverage step dies with a segmentation fault on your machine, it is Xdebug and
not the code: run that step with `php -d xdebug.mode=off`.

## Credits

- [ElPandaPe](https://github.com/elpandape)

## License

MIT. See [LICENSE.md](LICENSE.md).
