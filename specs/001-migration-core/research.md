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

- **Decision**: Each migration class is named `Migration{NNN}{PascalDescription}` and its version is the leading integer `NNN`. Ordering is strictly ascending by integer version. `getDescription()` supplies the human-readable summary.
- **Rationale**: Matches the IMS precedent (`Migration016AclRole`, `Migration017AclRule`) and the migration-layer design (`Migration{NNN}{PascalDescription}` naming).
- **Alternatives considered**: Timestamp-based naming (rejected — not deterministic for a seed/migration workflow that depends on fixed versions).

## R-004: Tracking table schema

- **Decision**: A `schema_migrations` table with `version` (integer, primary key), `description` (string), and `applied_at` (timestamp). Applied migrations are rows in this table.
- **Rationale**: Minimal, queryable, and the standard approach; supports the "list applied vs pending" inspection story.
- **Alternatives considered**: A JSON document of applied versions (rejected — harder to query and constrain uniqueness).

## R-005: CLI command layer

- **Decision**: Symfony Console commands (`migrate`, `status`, `rollback`) as thin adapters that dispatch the message-bus commands/queries. These commands are what webware-console discovers and surfaces.
- **Rationale**: The console PoC already validated Symfony Console; a thin command layer keeps the bus as the real boundary and gives webware-console a uniform command interface to surface.
- **Alternatives considered**: laminas-cli/mezzio-tooling (rejected for migration's own commands; mezzio-tooling commands are surfaced separately by the console).

## R-006: Concurrency and exactly-once

- **Decision**: v1 assumes one runner at a time per environment. Apply/revert run inside a database transaction so a failed step rolls back and is never recorded; a duplicate-version set is rejected at discovery. Cross-runner locking is deferred and documented as an edge case.
- **Rationale**: Satisfies the spec's "exactly-once" and "never mark a failed migration applied" requirements with minimal machinery.
- **Alternatives considered**: Advisory locks / a `running` flag (rejected for v1 — added complexity with no current concurrent-runner scenario).
