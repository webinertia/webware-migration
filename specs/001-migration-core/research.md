# Research: Migration Core

Phase 0 output — resolves the technical unknowns from the plan's Technical Context.

## R-001: Message-bus orchestration model

- **Decision**: Use the message-bus 2.0 command/query model. Mutating operations (run migrations, rollback) are commands dispatched to command handlers returning `CommandResult`; reads (list migrations, fetch applied) are queries dispatched to query handlers returning `QueryResult` with a component-owned payload.
- **Rationale**: Ecosystem-wide convention (locked 2026-08-27). Handlers are the single place that adapts repository output into read-models, so no persistence types cross the bus boundary.
- **Alternatives considered**: Direct service calls from consumers (rejected — breaks the read/write boundary convention and couples consumers to internals).

## R-002: Database abstraction

- **Decision**: php-db/phpdb `AdapterInterface` + `Sql`/DDL. The `schema_migrations` table is created via php-db DDL (`CreateTable`, `Integer`, `Varchar`). Portability across PostgreSQL, MySQL, and SQLite comes from the abstraction.
- **Rationale**: The constitution lists php-db/phpdb as the DB abstraction, and the webware stack (acl, usermanager) already uses the Adapter/RowPrototype contract.
- **Alternatives considered**: laminas-db (legacy, being phased out); raw PDO (portable but abandons the shared abstraction and DDL layer).

## R-003: Migration identity and ordering

- **Decision**: Each migration class is named `Migration{NNN}{PascalDescription}` (zero-padded `NNN`) and lives in the owning component's own namespace (e.g. `Webware\Acl\Migration\Migration001CreateRoles`). The version is the leading integer parsed from the filename; the owning package is known directly from the package that declared it (via its `MigrationProvider`). Within a package, order is the `glob()` filename order. Across packages, order is the Composer dependency graph (topological): a package's migrations run after the migrations of every package it requires.
- **Rationale**: Per-package versions remove cross-component version collisions; filename order is engine-level (no const introspection, no config ordering); the Composer graph is acyclic by construction and already resolved at install time.
- **Alternatives considered**: A single global integer version (rejected — collisions across components); a `VERSION` const plus config-list registration (rejected — more authoring and a "forgot to register" footgun); timestamp naming (rejected — not deterministic for seed workflows).

## R-004: Tracking table schema

- **Decision**: A `schema_migrations` table with `package` (string) and `version` (integer) as a composite primary key, plus `description` (string), `applied_at` (timestamp), and `checksum` (string). Applied migrations are rows keyed by `(package, version)`.
- **Rationale**: The composite key keeps each component's `001` distinct while remaining queryable; supports the "list applied vs pending" inspection story.
- **Alternatives considered**: `version`-only PK (rejected — cross-component collisions); a JSON document (rejected — harder to query and constrain uniqueness).

## R-005: CLI command layer

- **Decision**: Symfony Console commands (`migrate`, `status`, `rollback`) as thin adapters that dispatch the message-bus commands/queries. They live in `Webware\Migration\Console\` and are registered by `ConfigProvider` under `Webware\Console\ConsoleInterface::class` (`commands` map) so webware-console discovers them. This package provides ONLY the commands — it does NOT create the Symfony Application or a `bin/` entry point; that is webware-console's responsibility.
- **Rationale**: The console PoC already validated Symfony Console; a thin command layer keeps the bus as the real boundary, keeps the Application owned by the generic CLI host, and gives webware-console a uniform command interface to surface.
- **Alternatives considered**: laminas-cli/mezzio-tooling (rejected for migration's own commands; the `laminas-cli` config key belongs to mezzio-tooling and is merged separately by the console). Building the Symfony Application inside this package (rejected — webware-console owns the Application and `bin/`; migration stays a command-only library).

## R-006: Concurrency and exactly-once

- **Decision**: v1 assumes one runner at a time per environment. Apply/revert run inside a database transaction so a failed step rolls back and is never recorded; a duplicate-version set is rejected at discovery. Cross-runner locking is deferred and documented as an edge case.
- **Rationale**: Satisfies the spec's "exactly-once" and "never mark a failed migration applied" requirements with minimal machinery.
- **Alternatives considered**: Advisory locks / a `running` flag (rejected for v1 — added complexity with no current concurrent-runner scenario).

## R-007: Migration integrity checksum

- **Decision**: Record a SHA-256 checksum of each migration's source file in `schema_migrations.checksum` at apply time. Compute the checksum at discovery and compare it against the recorded value on inspection and before apply; on mismatch, fail with a clear "checksum mismatch" error.
- **Rationale**: Detects an already-applied migration that was edited later, so schema state can never silently diverge from the code that was run (Flyway does this for the same reason). It directly serves FR-011 and SC-006.
- **Alternatives considered**: No checksum (rejected — silent drift); hashing the class body via reflection (rejected — the source file is the canonical unit and the simplest to hash; file-less migrations are out of scope).

## R-008: Discovery via MigrationProvider

- **Decision**: Each migration-shipping package declares its migration surface through a `MigrationProviderInterface`, discovered via its Composer `extra.webware-migration.provider` key. The reconciler reads `extra`, instantiates the provider, and globs each of its `migrationPaths()` for `Migration*.php`; `pathinfo(..., PATHINFO_FILENAME)` yields the class name, and the zero-padded filename order is the within-package order. Adding a file is registration. Package identity is direct (the declaring package), not namespace-derived.
- **Rationale**: `extra` is a billboard developers read; the provider is the typed, `__DIR__`-aware contract (the laminas `extra` → `ConfigProvider` pattern, one level down); direct package identity removes the namespace → package reverse-mapping.
- **Alternatives considered**: ConfigProvider `migrations.paths` merge (rejected — no billboard, second source of truth); per-migration config registration (rejected — silent-skip footgun); shared `Webware\Migration\<Component>\` PSR-4 prefix (rejected — namespace inversion).

## R-009: Cross-package ordering and compatibility

- **Decision**: The runner orders packages by the Composer dependency graph (topological) and never checks compatibility. Compatibility is Composer's job: `require` for packages whose schema a migration depends on, and `conflict` (e.g. `"webware/acl": "<0.2.0"`) to forbid schema-incompatible combinations where a hard `require` would over-couple.
- **Rationale**: Composer will not install an incompatible version, so the runner can trust the install; ordering reduces to the already-resolved, acyclic package graph. No per-migration dependency declaration is needed.
- **Alternatives considered**: Migration-level `requires()` edges (rejected — duplicates composer.json and re-introduces per-migration authoring).

## R-010: Contracts are interfaces

- **Decision**: `MigrationRunnerInterface` (`migrate()`/`rollback()`) and `MigrationDiscoveryInterface` (directory-glob discovery) are interfaces; concrete `final readonly` classes implement them. `MigrationInterface` is behavior-only: `up()`, `down()`, `getDescription()` — the version is supplied by discovery (parsed from the filename), not by the instance.
- **Rationale**: Other components must be able to type-hint the contracts; concrete service classes are wiring details.
- **Alternatives considered**: `final readonly` concrete services (rejected — nothing outside the package can depend on them as a contract).

## R-011: Schema / Seed / Migration split

- **Decision**: Three artifacts, two lifecycles. Install (fresh) applies Schema (full declarative schema, owned by php-db) then Seed (base/reference data); Upgrade (version bump) applies Migrations (versioned deltas). A fresh install never replays migration history; an upgrade applies only the migrations between the recorded and installed versions.
- **Rationale**: Fresh installs stay fast and clean; schema definition lives declaratively in php-db rather than scattered across migration history; old migrations become squashable for new installs.
- **Alternatives considered**: Everything-is-a-migration (rejected — fresh installs replay history and schema definition is entangled with change history).

## R-012: Reconcile core

- **Decision**: A single `MigrationReconcilerInterface` compares installed packages (via `extra` + provider + `Composer\InstalledVersions` versions) against recorded state (`component_versions` + `schema_migrations`): no record → install (Schema + Seed); recorded version older than installed → upgrade (migrations); match → no-op. Checksums make migration state byte-exact.
- **Rationale**: One stateful core serves every trigger (CLI and Composer plugin) and makes the install-vs-upgrade decision from the tracking table, not the Composer verb.
- **Alternatives considered**: Keying off `install` vs `update` Composer events (rejected — `composer install` is also the deploy verb and carries committed bumps).

## R-013: Composer plugin trigger

- **Decision**: A Composer plugin subscribes to `post-install-cmd`/`post-update-cmd` (after the autoloader dump), resolves the reconciler + adapter from the hosting application's container (the host app's when hosted, webware-console's own scaffold when standalone), and runs the reconcile. The plugin wraps the adapter acquisition in try/catch (php-db `ExceptionInterface` + PSR-11 container exceptions) so an unreachable/unconfigured database is a no-op that never breaks `composer install`.
- **Rationale**: Matches `laminas-component-installer` (package-event-driven setup); the container is the source of DB config; the guard keeps Composer robust.
- **Alternatives considered**: Per-package `POST_PACKAGE_INSTALL` triggers (rejected — just-installed classes aren't autoloadable until the dump); deferred reconcile (rejected — the laminas precedent acts inline).

## R-014: Seed and provider contracts

- **Decision**: `SeedInterface` (distinct from `MigrationInterface`) holds a component's base/reference data and runs at install time. `MigrationProviderInterface` declares a package's migration surface (`migrationPaths()` + `seed()`; future `schema()`), discovered via `extra.webware-migration.provider`.
- **Rationale**: Seed has different semantics from a migration (install-time, full base data), so it warrants its own contract; the provider is the typed, per-package declaration seam.
- **Alternatives considered**: Seeds as `Migration{NNN}Seed{…}` migrations (rejected — conflates install-time data with upgrade deltas); provider metadata in ConfigProvider config (rejected — no billboard).

## R-015: Schema changes via php-db DDL types

- **Decision**: Migrations MUST express schema changes through php-db DDL types (`CreateTable`, `AlterTable`, `Column`, `Constraint`, `Sql`) — never raw SQL strings. Schema definition and introspection belong to php-db, not this package.
- **Rationale**: The DDL types are already php-db's schema-definition surface; when php-db ships declarative schema + differ, migrations generated from it will emit the same types, so nothing needs rewriting.
- **Alternatives considered**: Raw SQL in migrations (rejected — untransformable when the declarative-schema layer lands); a schema-definition abstraction in this package (rejected — php-db's future feature built in the wrong layer).
