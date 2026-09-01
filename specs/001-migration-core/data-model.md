# Data Model: Migration Core

## Entities

### Migration (delta, upgrade-time, not persisted)

- `package`: string — owning Composer package (e.g. `webware/acl`), known directly from the declaring package.
- `version`: int — positive, package-scoped; parsed from the `NNN` in the class/file name `Migration{NNN}{Description}`.
- `description`: string — human-readable summary, derived from the class name suffix.
- `up()`: apply the delta.
- `down()`: revert the delta.

### Seed (install-time, not persisted)

- A component's full base/reference data, applied at install time.
- `SeedInterface`: `seed()` applies it; it carries no version of its own — it is scoped to the component release.

### Schema (install-time, deferred — php-db)

- A component's full declarative schema, applied at install time.
- Owned by php-db (declarative schema + introspection); deferred until php-db ships it.
- Until then, schema is expressed through php-db DDL types inside migrations.

### MigrationProvider (in-memory declaration)

- A package's declaration of its migration surface: `migrationPaths()` and `seed()`.
- Discovered via the package's Composer `extra.webware-migration.provider` key.

### ComponentRecord (persisted record)

- `package`: string — primary key.
- `version`: string — the installed release version (e.g. `1.1.0`).
- `installedAt`: timestamp — when Schema + Seed were applied.

### AppliedMigration (persisted record)

- `package`: string — component of the composite primary key.
- `version`: int — component of the composite primary key.
- `description`: string — captured at apply time.
- `appliedAt`: timestamp — when it was applied.
- `checksum`: string — SHA-256 of the migration source file at apply time.

### MigrationSet (in-memory collection)

- Discovered `Migration` instances grouped by package; within a package ordered ascending by `version`; packages ordered by the Composer dependency graph.

## Relationships

- A `ComponentRecord` marks a package installed at a release version (Schema + Seed applied).
- An `AppliedMigration` corresponds to exactly one applied `Migration` by `(package, version)`.
- `MigrationSet` is derived from each package's `MigrationProvider` (glob); records come from `component_versions` and `schema_migrations`.

## State transitions

- `(absent)` → `installed`: on a fresh install; Schema + Seed run; a `component_versions` row is written at the installed version.
- `installed(v1)` → `installed(v2)`: on upgrade; each pending migration's `up()` runs; a `schema_migrations` row is written per migration; the `component_versions` row is bumped to `v2`.
- `applied` → `reverted`: on successful `down()`; the `schema_migrations` row is removed.

## Validation rules

- `version` MUST be a positive integer and unique within a package (FR-009).
- A migration MUST NOT be recorded applied unless `up()` completed (FR-010).
- A fresh install MUST NOT write `schema_migrations` rows (Schema + Seed are not migrations).
- Within a package, ordering MUST be ascending by `version` (filename order); across packages it MUST follow the Composer dependency graph (FR-002, FR-014).
- An applied migration whose current checksum differs from its recorded `checksum` MUST be reported as a mismatch and MUST NOT be trusted (FR-011).
