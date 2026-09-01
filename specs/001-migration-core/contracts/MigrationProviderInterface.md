# Contract: MigrationProviderInterface

Namespace: `Webware\Migration`

A ConfigProvider-style declaration of a package's migration surface.

## Signature

```php
interface MigrationProviderInterface
{
    /**
     * @return array{
     *     migrations: list<string>,
     *     seed: class-string<SeedInterface>|null,
     * }
     */
    public function __invoke(): array;
}
```

## Semantics

- `migrations` — directories to glob for `Migration*.php`, consumed by
  `MigrationDiscoveryInterface`.
- `seed` — the component's base-data class (resolved to a `SeedInterface`) or
  null when it has none; applied at install time.

## Discovery

- A package declares its provider in `composer.json` under
  `extra.webware-migration.provider`; the reconciler instantiates and invokes it
  — the laminas `extra` → `ConfigProvider` pattern.

## Future

- A `schema` key joins this array when php-db ships declarative schema.
