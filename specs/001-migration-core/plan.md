# Implementation Plan: Migration Core

**Branch**: `001-migration-core` | **Date**: 2026-08-28 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-migration-core/spec.md`

## Summary

Provide the core migration mechanism for the Webware component stack: a
`MigrationInterface` contract, deterministic discovery (directory glob) and
ordering (filename within a package, Composer dependency graph across packages),
a durable `(package, version)` tracking record so applied migrations are never
re-run, reverse-order rollback, and command-line commands for
migrate/status/rollback. Built as a PHP library using the message bus for
command/query orchestration and php-db for database abstraction. Applied
migrations are integrity-checked with a SHA-256 source checksum so a migration
modified after being applied is detected.

## Technical Context

**Language/Version**: PHP ~8.4.1 || ~8.5.0

**Primary Dependencies**: webware/webware-core (shared contracts), webware/message-bus ^2.0.0-beta.1 (command/query orchestration), php-db/phpdb (database abstraction), webware/webware-console (hard dep — supplies the Symfony Console command surface)

**Storage**: Relational database via the php-db abstraction — PostgreSQL, MySQL, SQLite; `schema_migrations` tracking table

**Testing**: PHPUnit 13.3 (strict: coverage metadata, mock/stub split), Infection mutation testing, Mago format/lint/analyze/guard

**Target Platform**: PHP 8.4/8.5 library + CLI for Mezzio applications

**Project Type**: library + cli

**Performance Goals**: No strict throughput target; discovery and state inspection must feel instant for typical migration counts (hundreds)

**Constraints**: Persistence layer bus-agnostic; no php-db types cross package boundaries; package-scoped identity; deterministic ordering (filename within a package, Composer graph across packages); exactly-once application

**Scale/Scope**: Shared library consumed by multiple webware components (acl, IMS); migrations numbered 001–999 per package

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Library-First** — PASS: single-purpose, self-contained migration library.
- **II. Owned Migration Logic** — PASS: interfaces, runner, tracking, discovery, and CLI commands all live in this package.
- **III. Bus-Aware, Persistence-Agnostic** — PASS: message bus used for orchestration; repositories return natural types; no php-db types cross boundaries.
- **IV. Webware Quality Gates** — PASS: PHPUnit 13 strict mode, Mago gates, Infection coverage are planned in.
- **V. Naming & Compatibility** — PASS: `Webware\Migration\` namespace; no redundant prefixes; PHP ~8.4.1 || ~8.5.0.

No violations requiring justification.

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
src/
├── MigrationInterface.php            # migration contract (up/down/getDescription)
├── Migration/
│   └── AbstractMigration.php         # optional base implementation
├── Runner/
│   ├── MigrationRunnerInterface.php  # migrate/rollback contract
│   ├── MigrationRunner.php           # concrete runner implementing the interface
│   ├── MigrationDiscoveryInterface.php # getPaths/discover contract
│   └── MigrationDiscovery.php        # directory glob + package/version derivation + ordering
├── ReadModel/
│   └── DiscoveredMigration.php       # package + version + migration instance
├── Repository/
│   ├── MigrationRepositoryInterface.php
│   └── PhpDbMigrationRepository.php  # bus-agnostic persistence (schema_migrations)
├── Command/
│   ├── RunMigrationsCommand.php
│   └── RollbackMigrationCommand.php
├── CommandHandler/
│   ├── RunMigrationsHandler.php
│   └── RollbackMigrationHandler.php
├── Query/
│   ├── ListMigrationsQuery.php
│   └── FetchAppliedMigrationsQuery.php
├── QueryHandler/
│   ├── ListMigrationsHandler.php
│   └── FetchAppliedMigrationsHandler.php
├── Console/                          # Symfony Console commands (migrate/status/rollback)
│   ├── MigrateCommand.php
│   ├── StatusCommand.php
│   └── RollbackCommand.php
├── Container/                        # factories for handlers + repository + runner
└── ConfigProvider.php                # DI wiring + command_map/query_map + command catalog registration

test/
├── unit/
└── integration/
```

**Structure Decision**: Single library package (`src/` + `test/`). Commands and
queries are the bus boundary; the runner orchestrates apply/revert; the
repository is the only php-db-touching layer. This package provides ONLY the
Symfony Console commands (`migrate`/`status`/`rollback` in
`Webware\Migration\Console\`) as thin adapters over the bus; it does NOT build
the Symfony Application or a `bin/` entry point — webware-console owns those and
discovers this package's commands via `ConsoleInterface`.

Discovery is directory-based: each component's `ConfigProvider` contributes its
migration directory under `migrations.paths`; `MigrationDiscovery` globs those
paths and derives each migration's package (from namespace) and version (from
filename). Contracts are interfaces (`MigrationRunnerInterface`,
`MigrationDiscoveryInterface`) so other packages type-hint the contracts, never
the concrete `final` classes.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations — this section is intentionally empty.
