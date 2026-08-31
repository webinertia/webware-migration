---
description: Session handoff for webware-migration — spec-kit state, architecture decisions, and next steps as of 2026-08-31.
applyTo: '**/*'
---

# Webware Migration Memory

Tagline: spec-kit scaffold + constitution/spec/plan MERGED; `/speckit-tasks` done (draft PR). Implementation DONE: source + unit/SQLite integration tests written; 100% line + mutation coverage (Infection min 96%); mago gates clean; five false-positive mutators ignored with reasons. US4 (console commands) implemented via `Webware\Console\ConsoleInterface` command registration. Multi-component redesign DECIDED 2026-08-31 (spec docs amended; src NOT yet updated).

## Component

- Package: `webware/webware-migration` — all migration logic for the Webware stack:
  `MigrationInterface`, runner, tracking (`schema_migrations`), discovery, and the CLI
  commands (`migrate`/`status`/`rollback`) that webware-console surfaces.
- Namespace: `Webware\Migration\`. PHP `~8.4.1 || ~8.5.0`.
- Runtime deps: `webware/webware-core` (contracts), `webware/message-bus` `^2.0.0-beta.1`,
  `php-db/phpdb` (PostgreSQL/MySQL/SQLite abstraction), `psr/container`,
  and **`webware/webware-console` (hard dep — brings Symfony Console)**. Dev: `webware/webware-tools`.
- **This package owns its Symfony commands** (`migrate`/`status`/`rollback` in `Webware\Migration\Console\`)
  and registers them for discovery by webware-console (see cross-component).

## Spec-kit state (specify 1.0.1)

- Scaffold (`specify init`) committed; `.specify/`, `.github/skills/`, and `specs/` ARE tracked.
  `.specify/feature.json` is gitignored (local-only). Root `.gitignore` = `/vendor/` only.
- Feature: `specs/001-migration-core/`.
  - constitution (`/speckit-constitution`) — MERGED.
  - spec (`/speckit-specify`) — MERGED (US1–US4; FR-001…FR-016).
  - plan (`/speckit-plan`) — MERGED (plan.md, research.md R-001…R-010, data-model.md,
    contracts/, quickstart.md).
  - tasks (`/speckit-tasks`) — DONE for T001–T034 (Phase 1–7 implemented); Phase 8
    (T035–T043, multi-component rework) pending.
- Workflow: each step = own branch + squash-merged PR to `0.1.x`. Conventional Commits;
  commit author must be `Joey Smith <jsmith@webinertia.net>` (never derive an identity).

## Locked architecture decisions

- Bus-aware, persistence-agnostic: message-bus command/query orchestration; repositories
  bus-agnostic (natural types only); no php-db `ResultSet`/`RowPrototype` crosses the bus boundary.
- `MigrationInterface`: `up(): void`, `down(): void`, `getDescription(): string`. NO `getVersion()` —
  version is parsed from the filename at discovery.
- Naming `Migration{NNN}{PascalDescription}` (zero-padded `NNN`) in the component's OWN namespace
  (e.g. `Webware\Acl\Migration\Migration001CreateRoles`); version is package-scoped.
- Tracking table `schema_migrations` with composite PK `(package, version)` + description, applied_at, checksum.
- Discovery: each component's `ConfigProvider` returns its migrations directory under
  `migrations.paths` (laminas-view `template_path_stack` style); the runner globs each path for
  `Migration*.php`. Adding a file is registration — no per-migration config, no `Webware\Migration`
  namespace shadowing.
- Ordering: within a package by filename (glob default alphabetical, zero-padded); across packages
  by the Composer dependency graph (topological).
- Compatibility: Composer's job (`require`/`conflict` in composer.json) — never checked by the runner.
- Contracts are interfaces: `MigrationRunnerInterface` (`migrate()`/`rollback()`),
  `MigrationDiscoveryInterface` (`getPaths()`/`discover()`). Concrete `final` classes implement them.
- **Integrity checksum (FR-011 / R-007)**: SHA-256 of the migration source file recorded at
  apply time; a mismatch on status/apply is a hard failure. One class per file; file-less out of scope.
- CLI: Symfony Console `migrate`/`status`/`rollback` as thin adapters over the bus; classes live
  in `Webware\Migration\Console\` (NOT moved to webware-console).
- Command registration: this package's `ConfigProvider` registers the commands under
  `Webware\Console\ConsoleInterface::class` (`commands` map) so webware-console's
  command loader discovers them. Do NOT use `laminas-cli` (that key is mezzio-tooling's; console merges it separately).
- Concurrency: v1 assumes one runner at a time; apply/revert run in a DB transaction so a
  failed step is never recorded.

## Cross-component

- `webware-console` is the generic CLI host (owns Symfony Application + `bin/` + config skeleton +
  command discovery); it is **migration-agnostic**. Dependency direction is one-way: **migration → console**.
- This package registers its commands via `ConfigProvider` under `ConsoleInterface::class`;
  console's `CommandLoaderFactory` reads that key (and merges `laminas-cli` for mezzio-tooling).
- `webware-acl` will ship `Migration016AclRole`/`Migration017AclRule` + a base-role seed
  (Guest/Member/Administrator) seeded via the DB; IMS builds on those. See webware-acl's Phase 4 note.
- The spec-kit `webware-alignment` preset lives in webware-tools; CI/alignment for this repo is a later step.

## Next actions

1. (DONE) `/speckit-tasks` for `001-migration-core` — `tasks.md` committed, draft PR.
2. (DONE) `/speckit-implement`: source + tests written; 100% line and mutation coverage
   (Infection `minMsi`/`minCoveredMsi` 96, five false-positive mutators ignored with reasons);
   `mago lint`/`analyze`/`guard` clean (no baselines).
3. (DONE) Open the implementation draft PR and squash-merge to `0.1.x`.
4. (DONE) US4 — Symfony Console commands (`migrate`/`status`/`rollback`) registered under
   `Webware\Console\ConsoleInterface` + `webware/webware-console` hard dep (`0.1.x-dev`).
5. CI/alignment with webware-tools (wrapper workflow, `mago.toml` extends, `phpunit.xml.dist`).
6. Queued 2026-08-29: strip the "no redundant namespace prefix" clause from Principle V here, in webware-console, and in the webware-tools `webware-alignment` preset constitution template.
7. (IN PROGRESS 2026-08-31) Multi-component redesign — decisions locked into `specs/001-migration-core/`
   (spec/plan/data-model/research/contracts/tasks/quickstart amended on branch
   `amend/multi-component-migrations`); implement Phase 8 tasks T035–T043 in `tasks.md`.
   NOT yet implemented in `src/`.
