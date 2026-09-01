# Quickstart

## 1. Define a migration

```php
use Webware\Migration\Migration\AbstractMigration;

final class Migration001CreateRoles extends AbstractMigration
{
    public function up(): void
    {
        // create the roles table
    }

    public function down(): void
    {
        // drop the roles table
    }
}
```

The version and description are derived from the class name
(`Migration{NNN}{PascalDescription}` → version `1`, description `Create Roles`).

## 2. Register migrations

Merge the migration list into the container config under the
`MigrationDiscovery::class` key:

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

## 3. Run the commands

Through webware-console:

```bash
php bin/webware migrate       # apply pending migrations
php bin/webware status        # list applied vs pending
php bin/webware rollback      # revert the most recent migration
```

`migrate` applies each pending migration exactly once, in version order, and
records it in `schema_migrations`. A second run reports "up to date" and does
nothing.
