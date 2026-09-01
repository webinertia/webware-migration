# Installation

## Requirements

- PHP 8.4.1+ (`~8.4.1 || ~8.5.0`)
- Composer
- A php-db adapter for your database (e.g. `php-db/phpdb-mysql`, `php-db/phpdb-sqlite`)

## Consumer applications

Install from within the consuming application:

```bash
composer require webware/webware-migration
```

`webware/webware-console` is a hard dependency and provides the Symfony Console
runtime; the `migrate`, `status`, and `rollback` commands are surfaced through
it.

## Development

When working on this package directly:

```bash
composer install
```
