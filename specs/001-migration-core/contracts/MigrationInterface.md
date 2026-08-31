# Contract: MigrationInterface

Namespace: `Webware\Migration`

## Signature

```php
interface MigrationInterface
{
    public function up(): void;

    public function down(): void;

    public function getDescription(): string;
}
```

## Semantics

- `up()` applies the change; MUST throw on failure so the runner does not record it.
- `down()` reverts the change; MUST throw on failure so the runner does not remove the record.
- `getDescription()` returns the human-readable summary derived from the class name suffix.

The version is NOT part of this contract. It is the leading zero-padded `NNN`
parsed from the class/file name `Migration{NNN}{Description}` at discovery time
(see [MigrationDiscoveryInterface](./MigrationDiscoveryInterface.md)).

## Implementers

- Any class implementing `MigrationInterface` in a registered migrations directory is discoverable.
- `AbstractMigration` (optional base) provides the description plumbing from the class name.

## Checksum

- The integrity checksum is computed from the migration's source file, not declared by the interface.
- Implementations are expected to live one class per source file; file-less migrations are out of scope.
