# Quickstart: Migration Core

Validation guide — proves the feature end-to-end. Implementation details belong in `tasks.md`.

## Prerequisites

- PHP 8.4.1+ (8.4 or 8.5).
- Composer.
- A php-db-supported database (SQLite is fastest for local validation).

## Steps

1. Install dependencies: `composer install`.

2. Define two sample migrations (e.g. `Migration001CreateRoles`, `Migration002AddRoleColumn`) in a migrations directory, each implementing [MigrationInterface](./contracts/MigrationInterface.md), and register the directory in a `ConfigProvider` under `migrations.paths`.

3. Run the migrate command.

   **Expected**: both migrations apply in ascending order; a `schema_migrations` table exists with two rows.

4. Run migrate again.

   **Expected**: no migration re-applies; output reports the schema is up to date (exit 0).

5. Run the status command.

   **Expected**: both migrations listed as applied, none pending.

6. Run the rollback command for one step.

   **Expected**: the second migration is reverted and its record removed; the first remains applied.

7. Re-run migrate to confirm the reverted migration applies again.

## Success

All acceptance scenarios in [spec.md](./spec.md) hold against a single SQLite database with no code changes to the migration definitions.
