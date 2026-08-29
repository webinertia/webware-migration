# Contract: CLI Commands

Exposed by this package; surfaced by webware-console.

| Command | Action | Exit status |
|---|---|---|
| `migrate` | Apply pending migrations in order | 0 on success (including "up to date"); non-zero on failure |
| `status` | List applied and pending migrations | 0 |
| `rollback [--steps=N]` | Revert the N most recent applied migrations (default 1) | 0 on success; non-zero on failure |

Output is human-readable text; the same exit-status contract holds whether a command is invoked directly or through the console.

`status` and `migrate` fail with a non-zero exit when an applied migration's checksum no longer matches its source.
