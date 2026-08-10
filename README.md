# Filament Bouncer

Roles and abilities for [Filament](https://filamentphp.com), built on
[silber/bouncer](https://github.com/JosephSilber/bouncer).

> **Early days.** `0.4.x` adds explicit denials, which is the whole reason this package
> exists. Nothing is closed yet: until the policies arrive, a panel with no policy of its
> own still lets anybody who reaches it manage roles. The API will change without a major
> bump while this package is on `0.x`.

## Why Bouncer

There is already an excellent package for roles and permissions on Filament built on
`spatie/laravel-permission`, and for most projects that is the right answer. This one exists
for the two things Bouncer does that spatie does not:

- **Explicit denials.** `forbid()` beats an allow, so "everything except deleting for good"
  is expressed as the exception it is, and survives the catalogue growing later.
- **Per-model and per-instance abilities.** "This editor only touches their own posts" is
  something Bouncer can store; spatie cannot.

If you need neither, you probably do not need this package.

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
delete them either.

## The roles screen

Subjects down the side, actions across the top. The columns are grouped by scope and the
group headings are tinted, so that "see a list" and "delete for good" cannot look like the
same decision.

Each cell holds one of three stances, and the middle one is not a quieter way of saying no:

| Stance | What the role is saying |
|---|---|
| **Granted** | This ability is given |
| **Not granted** | This role says nothing; the answer comes from whatever else the person holds |
| **Forbidden** | Nobody holding this role has it, whatever else grants it |

A denial beats a grant from another role and one made straight to the user. That is the one
thing Bouncer can express and `spatie/laravel-permission` cannot, and it is why "everything
except deleting for good" survives the catalogue growing later instead of quietly picking up
whatever gets added.

**Forbidding is offered on exactly the abilities granting is offered on, and no others.** It
would be arguable that restricting is a smaller power than granting, and the decision here
went the other way: a denial you cannot lift afterwards is a way to lock people out of
something you were never trusted with, so both go through the same gate.

Three things the screen refuses to do, and none of them is only a hidden button — each is
checked again where the write happens, so a request built by hand meets the same refusal:

- **Nobody decides about what they do not hold.** The grid is the catalogue narrowed to the
  abilities of whoever is filling it in, and the save is driven off that same narrowed
  catalogue rather than off what arrived in the request. A cell smuggled in for an ability
  they do not hold has nothing to match against, and a stance they cannot see is never
  overwritten either.
- **Nobody edits a role they hold themselves.** Otherwise raising your own reach is one save
  away.
- **Nobody edits the role that holds everything.** It is the way back in, and a way back in
  that can be edited is not one.

### The way back in

Handing out abilities is itself an ability, so a panel can be talked into a state where
nobody left is able to hand anything out. Name a role in `privileged_role` and
`filament-bouncer:reconcile` will make sure it exists and holds Bouncer's wildcard on every
run — including after somebody deletes it. `--check` fails while it is missing.

The wildcard is granted rather than every ability the catalogue holds today, so that a
resource added tomorrow is covered without anybody remembering to come back. That the
wildcard also grants abilities nobody ever declared is exactly what is wanted for this role,
and for no other.

## Configuration

| Key | What it decides |
|---|---|
| `panel` | The panel whose components declare the catalogue. `null` uses the default one |
| `navigation.icon` | The icon of the roles resource. `null` leaves it without one |
| `navigation.group` | The navigation group it belongs to. `null` leaves it ungrouped |
| `navigation.sort` | Its position. `null` leaves Filament's own ordering |
| `navigation.slug` | The path under the panel. Defaults to `security/roles` |
| `scopes` | Which actions count as reading, withdrawing and irreversible |
| `models` | Models with a policy but no resource, which would otherwise never be reached |
| `custom` | Abilities no component declares, as a map of name to scope |
| `ignore` | Resources, pages and widgets the catalogue leaves out |
| `privileged_role` | The role that holds everything, and that the screen refuses to edit |

The navigation keys are presentation decisions that belong to the application, not to the
package, which is why they are read from configuration rather than from a static property.

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
