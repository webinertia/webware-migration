# Webware Migration

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-migration)](https://packagist.org/packages/webware/webware-migration)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-migration)](https://packagist.org/packages/webware/webware-migration)
[![License](https://img.shields.io/github/license/webinertia/webware-migration)](LICENSE)
[![Continuous Integration](https://github.com/webinertia/webware-migration/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/webinertia/webware-migration/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-migration/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-migration)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-migration%2F0.1.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-migration/0.1.x)

`webware/webware-migration` is the migration component for the Webware stack. It
provides the migration contract, deterministic discovery and ordering, durable
applied-migration tracking with SHA-256 integrity checksums, reverse-order
rollback, and the `migrate`/`status`/`rollback` commands surfaced by
webware-console.

## Requirements

- PHP `~8.4.1 || ~8.5.0`

## Installation

```bash
composer require webware/webware-migration
```

## Usage

Define a migration:

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

Register discovered migrations in the container config and operate through the
`migrate`, `status`, and `rollback` commands provided by webware-console.

### Version convention

- `Migration000{Name}` — creates the component's schema.
- `Migration001{Name}` — seeds the component's base data.
- `Migration002{Name}` and up — ordinary migrations.

Schema and seed run before every later migration, so `000`/`001` are reserved
for them.

## Documentation

Versioned documentation lives under [`docs/`](docs/):

- [Installation](docs/v1/installation.md)
- [Quickstart](docs/v1/quickstart.md)
- [Commands](docs/v1/commands.md)
- [Configuration](docs/v1/configuration.md)
- [Development](docs/v1/development.md)
