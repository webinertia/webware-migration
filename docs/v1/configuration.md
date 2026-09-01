# Configuration

## Migration discovery

Migrations are declared as a list of service ids under the
`MigrationDiscovery::class` config key:

```php
use Webware\Migration\Runner\MigrationDiscovery;

return [
    MigrationDiscovery::class => [
        'migrations' => [
            Migration001CreateRoles::class,
            Migration002AddRoleColumn::class,
        ],
    ],
];
```

Each entry must resolve to a `Webware\Migration\MigrationInterface`.

## Database adapter

The repository persists to `schema_migrations` through a php-db adapter. The
package aliases `MigrationRepositoryInterface` to `PhpDbMigrationRepository`,
whose factory resolves the adapter from the container as
`PhpDb\Adapter\AdapterInterface` (provided by your database driver package).

## Message bus

Commands and queries are orchestrated through `webware/message-bus` 2.0. The
package's `ConfigProvider` wires the command/query maps and handler middleware
under `Webware\MessageBus\MessageBusInterface`.

## Command registration

The `migrate`/`status`/`rollback` commands are registered by `ConfigProvider`
under `Webware\Console\ConsoleInterface::class` (`commands` map); webware-console
discovers them at runtime.
