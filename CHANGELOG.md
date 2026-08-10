# Changelog

All notable changes to `elpandape/filament-bouncer` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). While the
version is below `1.0.0`, a minor bump may carry a breaking change.

## [0.5.0] - 2026-08-10

The panel closes. Until now nothing was actually shut: Filament asks a policy, and where
there is no policy it asks nobody and lets everybody through.

### Added

- A base policy that answers from Bouncer's clipboard rather than from the Gate, and
  carries no actions of its own, so that the methods a policy file declares are exactly
  the abilities its model offers.
- `filament-bouncer:policy`, which writes one for every resource of the panel whose model
  has none, leaves alone anything already there, and lets an application keep its own stub.
- A policy for the role model, registered by the package, so that the screen handing out
  abilities is itself governed by one. An application registering its own wins.
- `AuthorizesPage` and `AuthorizesWidget`, for the components that have no policy to ask.
- A boot guard that refuses to let a panel finish booting with a page or a widget that
  authorises nobody. It throws in production too; the `ignore` list is the way to say a
  component really is open to everybody.
- `--check` now also fails on a resource whose model has no policy, which closes the loop:
  the code declares, the catalogue derives, the store is reconciled, and the guard and the
  check refuse to let the three drift apart.

### Decided

- **No bulk actions on the roles table, and no `deleteAny` in the role policy.** Filament
  authorises a bulk delete once for the whole selection, and the two refusals keeping the
  privileged role and your own role out of reach live on the resource, so a bulk delete
  would walk past both. An ability nothing asks about has no business on the grid.

### Changed

- The roles screen now takes a grant to reach, like everything else. After upgrading,
  assign somebody the privileged role before expecting to get in.

## [0.4.0] - 2026-08-10

Explicit denials, which is the reason this package exists rather than the one built on
`spatie/laravel-permission`.

### Added

- A third stance in every cell. A role now grants, says nothing, or forbids, and a denial
  beats a grant from another role and one made straight to the user.
- The same three states on the detail screen, so a role is read in the shape it is written.

### Decided

- **Forbidding is offered on exactly the abilities granting is offered on.** It is arguable
  that restricting is the smaller power and could be handed out more freely; the decision
  went the other way, because a denial nobody can lift afterwards locks people out of
  something the person setting it was never trusted with.

### Changed

- The grid's state is now a stance per cell rather than a boolean. Anything reading or
  writing that state has to be updated.

## [0.3.0] - 2026-08-10

The roles screen: subjects down the side, actions across the top.

### Added

- A Filament plugin registering a roles resource, with a list, a create screen, an edit
  screen and a detail screen that shows the same grid out of reach.
- A grid of one checkbox per subject and action, its columns grouped by scope and its group
  headings tinted, so that reading a list and deleting for good cannot look alike.
- Three refusals, each checked where the write happens and not only on the button: nobody
  hands out an ability they do not hold themselves, nobody edits a role they hold, and
  nobody edits the role that holds everything.
- A `privileged_role` setting, and a reconcile run that makes sure that role exists and
  holds the wildcard — the way back in when handing out abilities has itself been handed
  away. `--check` fails while it is missing.

### Not included

No policies: until they arrive, a panel that has none of its own still lets anybody who
reaches it manage roles. No explicit denials yet either — a cell is ticked or it is not.

## [0.2.0] - 2026-08-10

The catalogue: what the panel is able to ask about, derived from the code that asks.

### Added

- A catalogue built by walking a panel's resources, pages and widgets. A resource
  contributes one ability per method its model's policy declares; a page and a widget
  contribute one each; a model with no policy contributes nothing.
- Four scopes — reading, writing, withdrawing, irreversible — that every action is sorted
  into, so that a screen can weigh them differently.
- Configuration for the panel to walk, for models with a policy but no resource, for
  abilities no component declares, and for components to leave out.
- `filament-bouncer:reconcile`, which creates the missing abilities in a single insert,
  reports the ones the catalogue no longer declares, deletes them with `--prune`, and with
  `--check` writes nothing and fails on any difference.

### Not included

No Filament resource and no screens: roles are still edited by hand. No policies, no panel
guard, and no migration path from `spatie/laravel-permission`.

## [0.1.0] - 2026-08-10

The first published version. It is deliberately a walking skeleton: its purpose is to prove
the release pipeline end to end — repository, continuous integration, tag, Packagist, and a
`composer require` that boots inside a real Filament panel — before any feature depends on
it.

### Added

- A service provider registered through package discovery.
- A publishable configuration file covering how the roles resource presents itself in the
  panel: icon, navigation group, sort order and slug.
- A test suite on Orchestra Testbench pinning that the provider registers, that the settings
  merge under their own key, and that Bouncer's tables come up.
- Continuous integration for code style, static analysis and tests.

### Not included

No Filament resource, no screens, no permission catalogue, no synchronisation command, no
panel guard, and no migration path from `spatie/laravel-permission`. All of that is later
work.

[0.5.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.5.0
[0.4.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.4.0
[0.3.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.3.0
[0.2.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.2.0
[0.1.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.1.0
