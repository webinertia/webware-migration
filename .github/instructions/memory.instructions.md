---
description: Session handoff for webware-migration — spec-kit state, architecture decisions, and next steps as of 2026-08-29.
applyTo: '**/*'
---

# Webware Migration Memory

Tagline: Full spec-kit scaffold + constitution/spec/plan all MERGED. Next: `/speckit-tasks`, then implement. The checksum integrity requirement (FR-011) is in the spec.

## Component

- Package: `webware/webware-migration` — all migration logic for the Webware stack:
  `MigrationInterface`, runner, tracking (`schema_migrations`), discovery, and the CLI
  commands (`migrate`/`status`/`rollback`) that webware-console surfaces.
- Namespace: `Webware\Migration\`. PHP `~8.4.1 || ~8.5.0`.
- Runtime deps: `webware/webware-core` (contracts), `webware/message-bus` `^2.0.0-beta.1`,
  `php-db/phpdb` (PostgreSQL/MySQL/SQLite abstraction). Dev: `webware/webware-tools`.

## Spec-kit state (specify 1.0.1)

- Scaffold (`specify init`) committed; `.specify/`, `.github/skills/`, and `specs/` ARE tracked.
  `.specify/feature.json` is gitignored (local-only). Root `.gitignore` = `/vendor/` only.
- Feature: `specs/001-migration-core/`.
  - constitution (`/speckit-constitution`) — MERGED.
  - spec (`/speckit-specify`) — MERGED (US1–US4; FR-001…FR-011).
  - plan (`/speckit-plan`) — MERGED (plan.md, research.md R-001…R-007, data-model.md,
    contracts/, quickstart.md).
  - tasks (`/speckit-tasks`) — NOT STARTED. Next step.
- Workflow: each step = own branch + squash-merged PR to `0.1.x`. Conventional Commits;
  commit author must be `Joey Smith <jsmith@webinertia.net>` (never derive an identity).

## Locked architecture decisions

- Bus-aware, persistence-agnostic: message-bus command/query orchestration; repositories
  bus-agnostic (natural types only); no php-db `ResultSet`/`RowPrototype` crosses the bus boundary.
- `MigrationInterface`: `getVersion(): int`, `getDescription(): string`, `up(): void`, `down(): void`.
- Naming `Migration{NNN}{PascalDescription}`; ascending integer version ordering.
- Tracking table `schema_migrations` (version PK, description, applied_at, checksum).
- **Integrity checksum (FR-011 / R-007)**: SHA-256 of the migration source file recorded at
  apply time; a mismatch on status/apply is a hard failure. One class per file; file-less out of scope.
- CLI: Symfony Console `migrate`/`status`/`rollback` as thin adapters over the bus.
- Concurrency: v1 assumes one runner at a time; apply/revert run in a DB transaction so a
  failed step is never recorded.

## Cross-component

- `webware-console` surfaces this package's CLI commands (TUI menu + help); it does not reimplement them.
- `webware-acl` will ship `Migration016AclRole`/`Migration017AclRule` + a base-role seed
  (Guest/Member/Administrator) seeded via the DB; IMS builds on those. See webware-acl's Phase 4 note.
- The spec-kit `webware-alignment` preset lives in webware-tools; CI/alignment for this repo is a later step.

## Next actions

1. `/speckit-tasks` for `001-migration-core` (branch + PR).
2. `/speckit-implement` ⇄ `/speckit-converge`.
3. CI/alignment with webware-tools (wrapper workflow, `mago.toml` extends, `phpunit.xml.dist`).
4. Queued 2026-08-29: strip the "no redundant namespace prefix" clause from Principle V here, in webware-console, and in the webware-tools `webware-alignment` preset constitution template.
