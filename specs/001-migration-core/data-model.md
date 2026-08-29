# Data Model: Migration Core

## Entities

### Migration (behavior, not persisted)

- `version`: int — unique, positive, determines ordering.
- `description`: string — human-readable summary of the change.
- `up()`: apply step.
- `down()`: revert step.

### AppliedMigration (persisted record)

- `version`: int — primary key, references the applied Migration.
- `description`: string — captured at apply time.
- `appliedAt`: timestamp — when it was applied.

### MigrationSet (in-memory collection)

- Ordered list of discovered `Migration` instances, sorted ascending by `version`.

## Relationships

- An `AppliedMigration` corresponds to exactly one applied `Migration` by `version`.
- `MigrationSet` is derived from discovered migrations; `AppliedMigration` rows come from the `schema_migrations` table.

## State transitions

- `pending` → `applied`: on successful `up()`; a row is written to `schema_migrations`.
- `applied` → `reverted`: on successful `down()`; the `schema_migrations` row is removed.

## Validation rules

- `version` MUST be a positive integer and unique within a set (FR-009).
- A migration MUST NOT be recorded applied unless `up()` completed (FR-010).
- Ordering MUST be ascending by `version` (FR-002).
