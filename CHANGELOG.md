# Changelog

All notable changes to `kuroragi/general-helper` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.1.0] - 2026-04-02

### Added
- `Authorization Exception Handler` — auto-redirect on `AuthorizationException` (403) with configurable redirect type (`route`, `url`, `back`, `home`), flash message, and JSON/AJAX support.
- `config_version` key in `kuroragi.php` to allow detection of stale published configs after upgrades.
- Comprehensive unit test suite (`tests/Unit/`) covering `GeneralHelper`, `ActivityLogger`, `ActivityLogReader`, and Macros.
- `phpunit.xml` config for PHPUnit 10/11.
- `require-dev` dependencies: `orchestra/testbench`, `phpunit/phpunit`.
- `keywords`, `homepage`, `support`, and `scripts.test` to `composer.json` for Packagist discoverability.

### Changed
- `spatie/laravel-permission` and `barryvdh/laravel-dompdf` moved from `require` to `suggest` — the package no longer forces these as hard dependencies.
- `GeneralHelperServiceProvider` now correctly registers `BlueprintMacros` in `boot()` (previously `BlueprintMacros::register()` was never called).

### Fixed
- **Critical** — `Blameable::currentAuthId()` now uses `Auth::id()` instead of `Auth::user()->id`, preventing a fatal `null->id` error when no user is authenticated.
- **Blueprint macros** (`$table->blameable()`, `$table->blameable()`) were silently unregistered because `BlueprintMacros::register()` was missing from the service provider boot sequence.

---

## [1.0.0] - 2025-11-01

### Added
- `Blameable` trait — auto-fill `created_by`, `updated_by`, `deleted_by` on Eloquent model events.
- `BlueprintMacros` — `blameable()`, `createdBy()`, `updatedBy()`, `deletedBy()`, `dropBlameable()` migrations macros.
- `EloquentMacros` — `createdBy($id)`, `updatedBy($id)`, `deletedBy($id)` query builder macros.
- `ActivityLogger` — file-based daily activity log service with JSON-per-line format.
- `ActivityLogReader` — read, search (keyword/category), and range-filter activity logs including `.zip` archives.
- `RollActivityLogs` artisan command — compress previous week's daily logs into a `.zip` archive.
- `GeneralHelper` class — `getSlug()`, `convertDateToIndo()`, `convertDateToIndoShort()`, `getTerbilang()`, `getIndoDate()`, `getIndoDateTerbilang()`.
- Auto-scheduled weekly log roll via `Schedule` in service provider.
- `config/kuroragi.php` with full configuration for log path, roll schedule, default reader limit, auth model, and authorization exception handler.

[Unreleased]: https://github.com/kuroragi/general-helper/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/kuroragi/general-helper/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/kuroragi/general-helper/releases/tag/v1.0.0
