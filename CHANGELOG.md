# Changelog

All notable changes to `elpandape/filament-bouncer` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). While the
version is below `1.0.0`, a minor bump may carry a breaking change.

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

[0.1.0]: https://github.com/elpandape/filament-bouncer/releases/tag/v0.1.0
