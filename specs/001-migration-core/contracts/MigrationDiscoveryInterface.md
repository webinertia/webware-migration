# Contract: MigrationDiscoveryInterface

Namespace: `Webware\Migration\Runner`

## Signature

```php
interface MigrationDiscoveryInterface
{
    /**
     * The merged migration directories from every component's ConfigProvider
     * (`migrations.paths`, accumulated by config-aggregator).
     *
     * @return list<string>
     */
    public function getPaths(): array;

    /**
     * Globs each path for `Migration*.php`, derives each migration's package
     * and version, and returns them ordered by the Composer dependency graph
     * (packages) then filename (version).
     *
     * @return list<DiscoveredMigration>
     */
    public function discover(): array;
}
```

## Semantics

- `getPaths()` returns the paths injected from the merged container config.
  Every component's `ConfigProvider` contributes to `migrations.paths`;
  config-aggregator appends each numeric-keyed entry, so all directories
  survive and their order is irrelevant.
- `discover()` reads the filesystem: for each path it runs `glob('Migration*.php')`,
  and `pathinfo(..., PATHINFO_FILENAME)` yields the class name whose leading
  zero-padded `NNN` is the version. The owning package is derived from the class
  namespace via Composer's PSR-4 map.

## Ordering

- Packages are ordered topologically by the Composer dependency graph.
- Within a package, migrations are ordered ascending by version (filename order).

## Return element

- `DiscoveredMigration` is a readonly value object carrying `package` (string),
  `version` (int), and `migration` (`MigrationInterface`).
