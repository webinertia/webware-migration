# Commands

The package registers three Symfony Console commands under
`Webware\Console\ConsoleInterface`; webware-console discovers and surfaces them.

| Command | Action | Exit status |
| --- | --- | --- |
| `migrate` | Apply pending migrations in order | 0 on success (including "up to date"); non-zero on failure |
| `status` | List applied and pending migrations | 0 |
| `rollback [--steps=N]` | Revert the N most recent applied migrations (default 1) | 0 on success; non-zero on failure |

`migrate` and `status` fail with a non-zero exit when an applied migration's
source has changed since it was applied (SHA-256 integrity checksum mismatch).
