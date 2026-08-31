# Data Model: Migration Core

## Entities

### Migration (behavior, not persisted)

- `package`: string — owning Composer package (e.g. `webware/acl`), derived from the migration's namespace.
- `version`: int — positive, package-scoped; parsed from the `NNN` in the class/file name `Migration{NNN}{Description}`.
- `description`: string — human-readable summary, derived from the class name suffix.
- `up()`: apply step.
- `down()`: revert step.

### AppliedMigration (persisted record)

- `package`: string — component of the composite primary key.
- `version`: int — component of the composite primary key.
- `description`: string — captured at apply time.
- `appliedAt`: timestamp — when it was applied.
- `checksum`: string — SHA-256 of the migration source file at apply time.

### MigrationSet (in-memory collection)

- Discovered `Migration` instances grouped by package; within a package ordered ascending by `version`; packages ordered by the Composer dependency graph.

## Relationships

- An `AppliedMigration` corresponds to exactly one applied `Migration` by `(package, version)`.
- `MigrationSet` is derived from discovered migrations (directory glob); `AppliedMigration` rows come from the `schema_migrations` table.

## State transitions

- `pending` → `applied`: on successful `up()`; a row is written to `schema_migrations`.
- `applied` → `reverted`: on successful `down()`; the `schema_migrations` row is removed.

## Validation rules

- `version` MUST be a positive integer and unique within a package (FR-009).
- A migration MUST NOT be recorded applied unless `up()` completed (FR-010).
- Within a package, ordering MUST be ascending by `version` (filename order); across packages it MUST follow the Composer dependency graph (FR-002, FR-014).
- An applied migration whose current checksum differs from its recorded `checksum` MUST be reported as a mismatch and MUST NOT be trusted (FR-011).
