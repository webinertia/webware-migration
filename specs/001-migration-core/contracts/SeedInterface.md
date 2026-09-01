# Contract: SeedInterface

Namespace: `Webware\Migration`

## Signature

```php
interface SeedInterface
{
    public function seed(): void;
}
```

## Semantics

- `seed()` inserts the component's full base/reference data.
- Runs at install time, after the component's Schema — never as an upgrade migration.
- MUST throw on failure so the install is not recorded as complete.

## Implementers

- A component implements `SeedInterface` and exposes it through its
  `MigrationProviderInterface::seed()`.

## Versioning

- A Seed carries no version of its own; it is scoped to the component release.
  New base data in a later release is a Migration (data delta), not an edit to
  an applied Seed.
