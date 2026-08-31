# Contract: MigrationRunnerInterface

Namespace: `Webware\Migration\Runner`

## Signature

```php
interface MigrationRunnerInterface
{
    public function migrate(): array;

    public function rollback(int $steps = 1): array;
}
```

## Semantics

- `migrate()` applies every pending migration in discovery order (Composer graph,
  then filename), recording each `(package, version)` only on success.
- `rollback($steps)` reverts the most recent applied migration(s) in reverse
  order, removing each record only on success.
- Both run inside a database transaction so a failed step is never recorded.
- Return values report the identities (`package` + `version`) that were applied
  or reverted.

## Implementers

- The concrete runner wires `MigrationDiscoveryInterface` + repository + checksum
  behind this contract.
- Consumers type-hint `MigrationRunnerInterface`, never the concrete class.
