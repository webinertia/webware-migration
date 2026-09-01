# Contract: Bus Boundaries

## Queries (read)

- `ListMigrationsQuery` → payload `array<int, MigrationInfo>` where `MigrationInfo` is a readonly read-model (`package`, `version`, `description`, `status: applied|pending`).
- `FetchAppliedMigrationsQuery` → payload `array<int, AppliedMigration>` where `AppliedMigration` is a readonly read-model (`package`, `version`, `description`, `appliedAt`, `checksum`).

## Commands (write)

- `ReconcileCommand` → `CommandResult`; installs (Schema + Seed) or upgrades (migrations) based on recorded state.
- `RunMigrationsCommand` → `CommandResult`; applies all pending migrations in version order (upgrade path).
- `RollbackMigrationCommand` (`steps` or target `version`) → `CommandResult`; reverts applied migrations in reverse order.

## Boundary rules

- Handlers are the only code that touch the repository and adapt its natural-type output into the read-models above.
- No php-db `ResultSet`/`RowPrototype` type appears in any payload.
- `getResult()` is `mixed`; each query's `@return` docblock declares the payload type.
- A checksum mismatch on an applied migration is reported as a failure (status/apply), never silently ignored.
