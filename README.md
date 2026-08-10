# Filament Bouncer

Roles and abilities for [Filament](https://filamentphp.com), built on
[silber/bouncer](https://github.com/JosephSilber/bouncer).

> **Early days.** `0.1.x` is a walking skeleton: it registers a service provider and
> publishes a configuration file, and that is all it does today. It exists so the release
> pipeline is proven before any feature depends on it. The API will change without a major
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

The service provider is registered through package discovery, so there is nothing to add by
hand.

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

## Configuration

| Key | What it decides |
|---|---|
| `navigation.icon` | The icon of the roles resource. `null` leaves it without one |
| `navigation.group` | The navigation group it belongs to. `null` leaves it ungrouped |
| `navigation.sort` | Its position. `null` leaves Filament's own ordering |
| `navigation.slug` | The path under the panel. Defaults to `security/roles` |

These are presentation decisions that belong to the application, not to the package, which is
why they are read from configuration rather than from a static property.

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

## Credits

- [ElPandaPe](https://github.com/elpandape)

## License

MIT. See [LICENSE.md](LICENSE.md).
