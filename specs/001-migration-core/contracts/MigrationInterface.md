# Contract: MigrationInterface

Namespace: `Webware\Migration`

## Signature

```php
interface MigrationInterface
{
    public function getVersion(): int;

    public function getDescription(): string;

    public function up(): void;

    public function down(): void;
}
```

## Semantics

- `getVersion()` returns the integer version (the `NNN` from `Migration{NNN}...`).
- `getDescription()` returns a human-readable summary of the change.
- `up()` applies the change; MUST throw on failure so the runner does not record it.
- `down()` reverts the change; MUST throw on failure so the runner does not remove the record.

## Implementers

- Any class implementing `MigrationInterface` is discoverable by the runner.
- `AbstractMigration` (optional base) provides description/version plumbing from the class name.

## Checksum

- The integrity checksum is computed from the migration's source file, not declared by the interface.
- Implementations are expected to live one class per source file; file-less migrations are out of scope.
