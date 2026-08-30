<!--
Sync Impact Report
==================
Version change: 1.0.0 → 1.1.0
Modified principles: II clarified (migration provides ONLY Symfony Console commands; webware-console owns the Application + bin)
Modified sections: Dependencies & Compatibility (added webware/webware-console hard dep)
Added sections: none
Removed sections: none
Follow-up TODOs: none
-->

# Webware Migration Constitution

## Core Principles

### I. Library-First
Every capability ships as a self-contained, independently testable library component. Components MUST have a clear, singular purpose; organizational-only or kitchen-sink packages are prohibited.

### II. Owned Migration Logic
All migration logic lives in this package: the `Webware\Migration\MigrationInterface` contract (`getVersion`, `getDescription`, `up`, `down`), its implementations, the migration runner, tracking-table management, discovery, and the CLI commands that consumers invoke. This package provides ONLY the Symfony Console commands; it does NOT create the Symfony Application or a `bin/` entry point. Webware Console owns the Application and `bin/`, carries a hard dependency on this package, and surfaces these commands without reimplementing them.

### III. Bus-Aware, Persistence-Agnostic
The package MAY use the message bus (commands and queries) for its own orchestration. Persistence (repositories/adapters) stays bus-agnostic — pure storage with no message-bus types. Natural PHP types only across package boundaries: no php-db result-set or row-prototype types may leak into consumers.

### IV. Webware Quality Gates (NON-NEGOTIABLE)
Every change MUST pass the shared webware gates: Mago format, lint, analyze, and guard with no silent suppression; PHPUnit 13 strict mode (coverage metadata, mock/stub split, `failOnNotice`/`failOnDeprecation`/`failOnWarning`); and Infection mutation coverage at or above the configured thresholds. Test doubles follow PHPUnit 13 rules — `createStub()` for value doubles, `createMock()` only with `expects()`.

### V. Naming & Compatibility
Class and interface names MUST NOT add redundant descriptive prefixes that repeat the enclosing namespace. Interface names end in `Interface`; trait names end in `Trait`. Support only current supported PHP versions (`~8.4.1 || ~8.5.0`). This package depends on `webware/webware-core` for shared contracts.

## Dependencies & Compatibility

- `webware/webware-core` supplies shared contracts (e.g. `SchemaInterface`).
- `php-db/phpdb` provides the database abstraction (PostgreSQL, MySQL, SQLite) for migration persistence.
- `webware/message-bus` is available for command/query orchestration when the migration logic needs it.
- `webware/webware-console` is a hard runtime dependency supplying the Symfony Console command surface; this package contributes commands, not the Application.
- `webware/webware-tools` is a development-only dependency supplying the shared Mago configuration and CI conventions.
- VCS repository entries appear only for pre-release dev dependencies and are removed once the dependency is tagged on Packagist.

## Development Workflow

- Spec-driven development: each step (constitution, specify, plan, tasks, implement, converge) lands as its own branch and squash-merged pull request.
- Commits use Conventional Commits and carry the maintainer's real identity (`Joey Smith <jsmith@webinertia.net>`).

## Governance

- This constitution supersedes other practices; conflicts are resolved in its favor or the constitution is amended via pull request.
- Amendments require a pull request that updates the version and Last Amended date.
- Every pull request is reviewed for compliance with the Core Principles.

**Version**: 1.1.0 | **Ratified**: 2026-08-28 | **Last Amended**: 2026-08-29
