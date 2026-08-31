# Feature Specification: Migration Core

**Feature Branch**: `001-migration-core`

**Created**: 2026-08-28

**Status**: Draft

**Input**: User description: "Build the Webware migration component: define, discover, track, apply, and revert schema/data migrations, and expose them as commands a console can surface."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Define and apply migrations (Priority: P1)

A developer describes a schema or data change as a versioned migration with an apply step and a revert step. When the developer runs the migrate operation, the system discovers every migration that has not yet been applied, applies each one in version order, and records it as applied so it is never run twice.

**Why this priority**: This is the core value — without it there is no migration mechanism at all. Inspection, rollback, and the command-line surface all build on applied migrations being tracked reliably.

**Independent Test**: Can be fully tested by defining two ordered migrations and running the migrate operation — both apply in order, and a second run applies nothing and reports a clean, up-to-date state.

**Acceptance Scenarios**:

1. **Given** two unapplied migrations (v1, v2), **When** the migrate operation runs, **Then** both are applied in ascending version order and each is recorded as applied.
2. **Given** all migrations are already applied, **When** the migrate operation runs again, **Then** no migration is re-applied and the result reports that the schema is up to date.
3. **Given** a new migration is added after earlier ones were applied, **When** the migrate operation runs, **Then** only the new migration is applied.

---

### User Story 2 - Inspect migration state (Priority: P2)

A developer or operator can list which migrations are applied and which are pending, and see the current state of an environment, without changing anything.

**Why this priority**: Read-only visibility is required to diagnose environments and to trust that migration state is correct; it is independently valuable and lower-risk than applying or reverting.

**Independent Test**: Can be fully tested by applying a subset of migrations and confirming the inspection output lists exactly the applied and pending migrations, without altering any data.

**Acceptance Scenarios**:

1. **Given** migrations v1 and v2 where only v1 is applied, **When** the inspection operation runs, **Then** it reports v1 as applied and v2 as pending.
2. **Given** a fresh environment with no applied migrations, **When** the inspection operation runs, **Then** it reports all discovered migrations as pending.

---

### User Story 3 - Revert migrations (Priority: P2)

A developer can revert applied migrations, in reverse order of application, to undo a change that was applied incorrectly or is no longer wanted.

**Why this priority**: Reversibility is part of the migration contract (each migration has a revert step) and is expected by developers before relying on migrations in a real workflow.

**Independent Test**: Can be fully tested by applying two migrations and then reverting one — the most recent migration is reverted and its record removed, while the earlier one remains applied.

**Acceptance Scenarios**:

1. **Given** migrations v1 and v2 are applied, **When** a single revert runs, **Then** v2 is reverted (its revert step runs) and v2 is no longer recorded as applied, while v1 stays applied.
2. **Given** only v1 is applied, **When** a single revert runs, **Then** v1 is reverted and the environment returns to having no applied migrations.

---

### User Story 4 - Operate from the command line (Priority: P3)

An operator runs the apply, inspect, and revert operations as command-line commands with clear output and success/failure exit status, so the operations can be invoked manually and surfaced by a console.

**Why this priority**: The operations are already valuable programmatically; the command-line surface is the ergonomic layer that makes them usable day to day and is the interface a TUI console presents.

**Independent Test**: Can be fully tested by invoking each command from a shell and observing correct output and exit status for success and failure cases.

**Acceptance Scenarios**:

1. **Given** pending migrations exist, **When** the migrate command runs and succeeds, **Then** it exits with a success status and prints the migrations that were applied.
2. **Given** a migration fails, **When** the migrate command runs, **Then** it exits with a failure status and prints which migration failed and why.

---

### Edge Cases

- A migration fails partway through its apply step — the system MUST NOT mark it applied and MUST report the failure with the migration's identity.
- Two migrations in the same package declare the same version — the system MUST reject the set as ambiguous rather than applying nondeterministically.
- A revert step fails — the system MUST NOT mark the migration as reverted and MUST leave its record intact so state is not silently corrupted.
- Two migrate operations run at the same time — the system MUST ensure only one applies at a time (or fail the second safely), so migrations are never double-applied.
- Running migrate on an environment that is already up to date — MUST be a no-op with a clear "up to date" result.
- A migration whose revert has never been exercised — revert MUST still be available and must not corrupt state if it fails.
- An applied migration is modified after being applied — the system MUST detect the change and report it rather than silently trusting the stale record.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST let a developer define a migration as a versioned unit with an apply step and a revert step; the version is scoped to the owning package.
- **FR-002**: System MUST order migrations deterministically — by version within a package (filename order) and by the Composer dependency graph across packages.
- **FR-003**: System MUST apply each pending migration exactly once, in version order.
- **FR-004**: System MUST durably record each applied migration so a completed migration is never re-applied.
- **FR-005**: System MUST provide read-only inspection of applied and pending migrations without changing state.
- **FR-006**: System MUST revert applied migrations in reverse application order.
- **FR-007**: System MUST expose apply, inspect, and revert as command-line commands with clear output and correct exit status.
- **FR-008**: System MUST behave identically across the supported database systems (PostgreSQL, MySQL, SQLite).
- **FR-009**: System MUST reject a migration set containing duplicate `(package, version)` identities.
- **FR-010**: System MUST NOT mark a migration applied unless its apply step completed successfully.
- **FR-011**: System MUST detect when an already-applied migration has been modified since it was applied and MUST report the mismatch before applying further changes.
- **FR-012**: System MUST scope migration identity to the owning package, so two packages may each ship their own `001` without colliding.
- **FR-013**: System MUST discover migrations by globbing the migration directories each component registers via its `ConfigProvider` (no per-migration registration).
- **FR-014**: System MUST order migrations within a package by ascending version (filename order) and across packages by the Composer dependency graph.
- **FR-015**: System MUST NOT check package compatibility; compatibility is enforced by Composer (`require`/`conflict`) at install time.
- **FR-016**: The runner and discovery services MUST be exposed as interfaces so other packages can depend on the contracts.

### Key Entities *(include if feature involves data)*

- **Migration**: a versioned unit of change; has an owning package, a package-scoped version, a description, an apply behavior, and a revert behavior.
- **Applied migration record**: the durable record that a specific `(package, version)` migration was applied, including when it was applied.
- **Migration set**: the ordered collection of discovered migrations for an environment, grouped by package and ordered within a package by version and across packages by the Composer dependency graph.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer can define and apply a new migration with no changes to surrounding infrastructure beyond the migration itself.
- **SC-002**: Applying a set of N migrations is deterministic — running the apply operation twice on the same environment yields the identical schema state and zero errors.
- **SC-003**: 100% of migration records are accurate — no completed migration is ever re-applied and no failed migration is ever recorded as applied.
- **SC-004**: An operator can determine an environment's exact applied-versus-pending state with a single inspection command.
- **SC-005**: The same migration definitions run unmodified on every supported database system.
- **SC-006**: 100% of post-apply modifications to a migration are detected and reported before any further operation proceeds.

## Assumptions

- Migrations are authored by developers who understand the schema; end-users never write migrations directly.
- The command-line interface is the operator surface; this component provides only the Symfony Console commands (`migrate`/`status`/`rollback`). The Symfony Application and its `bin/` entry point are owned by webware-console (a hard dependency of this component) and are out of scope here.
- Supported database systems are PostgreSQL, MySQL, and SQLite, provided through a shared database abstraction.
- Seeding baseline data (e.g. default roles) is a consumer concern layered on top of migrations, not part of this component's core mechanism.
- The migrate operation runs one environment at a time; cross-environment coordination is out of scope for v1.
- Migrations live in the owning component's own namespace (e.g. `Webware\Acl\Migration\…`); components do NOT shadow the `Webware\Migration` namespace.
- Each component declares its migration directory via its `ConfigProvider` under `migrations.paths`; the runner discovers migrations by globbing those directories.
- Package compatibility is Composer's responsibility (`require`/`conflict`); the runner never checks versions or compatibility.
