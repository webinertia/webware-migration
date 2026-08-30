# Contract: CLI Commands

Exposed by this package; surfaced by webware-console.

These are Symfony Console commands in `Webware\Migration\Console\`
(`MigrateCommand`, `StatusCommand`, `RollbackCommand`) — thin adapters that
dispatch the message-bus commands/queries. This package provides ONLY the
commands: it does NOT create the Symfony Application or a `bin/` entry point.
webware-console owns the Application, `bin/`, and command discovery, and carries
a hard dependency on this package.

| Command | Action | Exit status |
|---|---|---|
| `migrate` | Apply pending migrations in order | 0 on success (including "up to date"); non-zero on failure |
| `status` | List applied and pending migrations | 0 |
| `rollback [--steps=N]` | Revert the N most recent applied migrations (default 1) | 0 on success; non-zero on failure |

Registration: `ConfigProvider` publishes the commands under
`Webware\Console\Catalog\CommandCatalogInterface::class` (`commands` map);
webware-console's `CommandCatalogFactory` reads that key. Do NOT use the
`laminas-cli` key (that is mezzio-tooling's; the console merges it separately).

Output is human-readable text; the same exit-status contract holds whether a command is invoked directly or through the console.

`status` and `migrate` fail with a non-zero exit when an applied migration's checksum no longer matches its source.
