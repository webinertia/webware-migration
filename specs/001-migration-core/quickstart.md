# Quickstart: Migration Core

Validation guide — proves the feature end-to-end. Implementation details belong in `tasks.md`.

## Prerequisites

- PHP 8.4.1+ (8.4 or 8.5).
- Composer.
- A php-db-supported database (SQLite is fastest for local validation).

## Steps

1. Install dependencies: `composer install`.

2. In a component, declare its migration surface in `composer.json`:
   ```json
   "extra": { "webware-migration": { "provider": "Webware\\Acl\\MigrationProvider" } }
   ```
   and implement [MigrationProviderInterface](./contracts/MigrationProviderInterface.md)
   (migration paths + optional [SeedInterface](./contracts/SeedInterface.md)).
   Until php-db ships declarative schema, the component's schema is expressed
   through php-db DDL types in its migrations.

3. Run the install command against an empty database.

   **Expected**: the component's schema (interim: php-db DDL) and seed apply;
   `component_versions` records the installed version; no `schema_migrations` rows.

4. Run install again.

   **Expected**: no-op — the component is already at its installed version.

5. Bump the component, add `Migration001AddRoleCode`, and run the migrate command.

   **Expected**: the delta applies and a `schema_migrations` row is written.

6. Run the status command.

   **Expected**: applied vs pending listed, with checksum state.

7. Run the rollback command for one step.

   **Expected**: the most recent migration reverts and its record is removed.

## Success

All acceptance scenarios in [spec.md](./spec.md) hold against a single SQLite database with no code changes to the migration definitions.
