# Contract: MigrationProviderInterface

Namespace: `Webware\Migration`

## Signature

```php
interface MigrationProviderInterface
{
    /**
     * Migration directories to glob for `Migration*.php`.
     *
     * @return list<string>
     */
    public function migrationPaths(): array;

    public function seed(): ?SeedInterface;
}
```

## Semantics

- A package that ships migrations implements this contract and declares it in
  `composer.json` under `extra.webware-migration.provider`.
- `migrationPaths()` returns `__DIR__`-relative directories; the reconciler globs
  each for `Migration*.php`.
- `seed()` returns the component's base data (or null when it has none), applied
  at install time.

## Discovery

- The reconciler reads `extra.webware-migration.provider`, instantiates the
  provider, and calls it — the laminas `extra` → `ConfigProvider` pattern.

## Future

- A `schema()` method joins this contract when php-db ships declarative schema.
