# Contract: MigrationReconcilerInterface

Namespace: `Webware\Migration`

## Signature

```php
interface MigrationReconcilerInterface
{
    public function reconcile(): array;
}
```

## Semantics

- `reconcile()` compares installed packages (providers + `Composer\InstalledVersions`)
  against recorded state (`component_versions` + `schema_migrations`):
  - no record → **install**: Schema + Seed;
  - recorded version older than installed → **upgrade**: run pending migrations;
  - match → **no-op**.
- Returns the identities (`package` + version) that were installed or upgraded.

## Triggers

- The Composer plugin and the CLI (`install` / `migrate`) both invoke this same
  core; only their wrapping differs. The plugin no-ops on an unreachable DB
  (catching php-db and PSR-11 exceptions); the CLI surfaces errors.
