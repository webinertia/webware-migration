# Tasks: Migration Core

**Input**: Design documents from `/specs/001-migration-core/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Included — the constitution's Quality Gates mandate 100% line + mutation coverage, so every story ships with unit and integration tests.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US4)

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Tooling and dependency baseline so all stories run green.

- [ ] T001 Add `webware/webware-core`, `webware/message-bus` (^2.0.0-beta.1), `php-db/phpdb`, and `webware/webware-console` (hard dep — Symfony Console) to `require` in `composer.json`; keep `webware/webware-tools` in `require-dev`; run `composer update`
- [ ] T002 [P] Create `phpunit.xml.dist` with `requireCoverageMetadata="true"`, `failOnNotice/failOnDeprecation/failOnWarning="true"`, and a `unit test` + `integration test` testsuite
- [ ] T003 [P] Create `mago.toml` that `extends = "vendor/webware/webware-tools/mago.toml"` and sets `php-version`, baseline paths, and source paths

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The contract and persistence seam every story depends on.

**⚠️ CRITICAL**: No user story work until this phase is complete.

- [ ] T004 Implement `MigrationInterface` in `src/MigrationInterface.php` (`getVersion(): int`, `getDescription(): string`, `up(): void`, `down(): void`)
- [ ] T005 Implement `AbstractMigration` in `src/Migration/AbstractMigration.php` (version/description plumbing from class name)
- [ ] T006 [P] Implement `MigrationRepositoryInterface` in `src/Repository/MigrationRepositoryInterface.php` (natural types only — `appliedVersions()`, `recordApplied()`, `removeApplied()`, `findApplied()`)
- [ ] T007 Implement `PhpDbMigrationRepository` in `src/Repository/PhpDbMigrationRepository.php` — creates `schema_migrations` (version PK, description, applied_at, checksum) via php-db DDL and maps rows to natural arrays; no bus types
- [ ] T008 [P] Implement read-models `AppliedMigration` (version, description, appliedAt, checksum) and `MigrationInfo` (version, description, status) in `src/ReadModel/`
- [ ] T009 [P] Unit test `MigrationInterface` + `AbstractMigration` in `test/unit/Migration/AbstractMigrationTest.php`

**Checkpoint**: contract + persistence seam ready; no story blocked on infrastructure.

## Phase 3: User Story 1 — Define and apply migrations (Priority: P1) 🎯 MVP

**Goal**: A developer defines ordered migrations; running migrate applies each pending one exactly once and records it.

**Independent Test**: Define two migrations, run migrate — both apply in order; a second run applies nothing.

### Tests for User Story 1

- [ ] T010 [P] [US1] Unit test discovery/ordering in `test/unit/Runner/MigrationDiscoveryTest.php`
- [ ] T011 [US1] Integration test apply-once in `test/integration/MigrationRunnerTest.php` (SQLite in-memory; two migrations; second run no-ops)

### Implementation for User Story 1

- [ ] T012 [P] [US1] Implement `MigrationDiscovery` in `src/Runner/MigrationDiscovery.php` — discover `MigrationInterface` classes, sort ascending, reject duplicate versions (FR-002/FR-009)
- [ ] T013 [P] [US1] Implement `MigrationChecksum` in `src/Runner/MigrationChecksum.php` — SHA-256 of the migration source file (R-007)
- [ ] T014 [US1] Implement `MigrationRunner` in `src/Runner/MigrationRunner.php` — apply pending migrations in order inside a transaction, record each only on success (FR-003/FR-010)
- [ ] T015 [US1] Implement `RunMigrationsCommand` in `src/Command/RunMigrationsCommand.php` and `RunMigrationsHandler` in `src/CommandHandler/RunMigrationsHandler.php`
- [ ] T016 [US1] Register `RunMigrationsCommand`/`RunMigrationsHandler` in `src/ConfigProvider.php` (`command_map`)

**Checkpoint**: US1 independently functional — apply-once verified.

## Phase 4: User Story 2 — Inspect migration state (Priority: P2)

**Goal**: List applied vs pending migrations read-only.

**Independent Test**: Apply a subset, run status — lists exactly applied and pending, no changes.

### Tests for User Story 2

- [ ] T017 [US2] Unit test list/status payload mapping in `test/unit/QueryHandler/ListMigrationsHandlerTest.php`

### Implementation for User Story 2

- [ ] T018 [P] [US2] Implement `ListMigrationsQuery` in `src/Query/ListMigrationsQuery.php` and `FetchAppliedMigrationsQuery` in `src/Query/FetchAppliedMigrationsQuery.php`
- [ ] T019 [US2] Implement `ListMigrationsHandler` and `FetchAppliedMigrationsHandler` in `src/QueryHandler/` — adapt repository output into read-models; no php-db types
- [ ] T020 [US2] Register the query handlers in `src/ConfigProvider.php` (`query_map`)

**Checkpoint**: US2 independently functional — status lists applied/pending without writes.

## Phase 5: User Story 3 — Revert migrations (Priority: P2)

**Goal**: Revert applied migrations in reverse order.

**Independent Test**: Apply two, rollback one — the second reverts, the first stays.

### Tests for User Story 3

- [ ] T021 [P] [US3] Unit test rollback ordering in `test/unit/Runner/MigrationRunnerTest.php`
- [ ] T022 [US3] Integration test rollback in `test/integration/MigrationRunnerTest.php` (apply two, revert one, assert record removed)

### Implementation for User Story 3

- [ ] T023 [US3] Implement `RollbackMigrationCommand` in `src/Command/RollbackMigrationCommand.php` and `RollbackMigrationHandler` in `src/CommandHandler/RollbackMigrationHandler.php`
- [ ] T024 [US3] Extend `MigrationRunner` with reverse-order rollback (run `down()`, remove record only on success) (FR-006)
- [ ] T025 [US3] Register the rollback command/handler in `src/ConfigProvider.php` (`command_map`)

**Checkpoint**: US3 independently functional — reverse-order rollback verified.

## Phase 6: User Story 4 — Operate from the command line (Priority: P3)

**Goal**: `migrate` / `status` / `rollback` commands with clear output and exit status.

**Independent Test**: Invoke each command from a shell; success and failure exit statuses are correct.

### Tests for User Story 4

- [ ] T026 [P] [US4] Unit test command output/exit status in `test/unit/Command/CommandTest.php`

### Implementation for User Story 4

- [ ] T027 [US4] Implement Symfony Console commands `MigrateCommand`/`StatusCommand`/`RollbackCommand` in `src/Console/` (namespace `Webware\Migration\Console\`) as thin adapters dispatching the bus commands/queries
- [ ] T028 [US4] Register `MigrateCommand`/`StatusCommand`/`RollbackCommand` in `src/ConfigProvider.php` under `Webware\Console\Catalog\CommandCatalogInterface::class` (`commands` map) for discovery by webware-console — do NOT add a `bin/` entry point (webware-console owns the Symfony Application)

**Checkpoint**: US4 independently functional — all three commands are discoverable by webware-console and run from the shell; this package owns no Symfony Application or `bin/` entry.

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Coverage gate, CI alignment, and final validation.

- [ ] T029 [P] Add checksum-mismatch coverage: modify an applied migration and assert `status`/`migrate` fail (FR-011)
- [ ] T030 [P] Run `mago format`/`lint`/`analyze`/`guard` and fix findings at source
- [ ] T031 Run `composer test` + `composer test-integration` and close coverage gaps to 100% line + mutation
- [ ] T032 Add the reusable-workflow wrapper `.github/workflows/continuous-integration.yml` + `codecov.yml` + `infection.json5.dist` + `renovate.json`
- [ ] T033 [P] Update `README.md` with badges and usage
- [ ] T034 Run `quickstart.md` validation end-to-end

## Dependencies & Execution Order

### Phase Dependencies

- Setup (Phase 1) → Foundational (Phase 2) → User Stories (Phases 3–6, P1→P2→P3) → Polish (Phase 7)
- Foundational blocks all user stories.

### User Story Dependencies

- US1: after Foundational; no story deps.
- US2: after Foundational; independent (read-only).
- US3: depends on US1 (rollback needs the runner/apply path).
- US4: depends on US1–US3 (commands wrap the operations).

### Within Each Story

- Tests first (fail before implementation) → models/contracts → services → commands → registration.

### Parallel Opportunities

- T002/T003 (setup tooling) and T006/T008 (foundational read-models/repository interface) are [P].
- US1's discovery/checksum tasks (T012/T013) are [P]; US2's queries (T018) are [P].
- US1 and US2 can be worked concurrently once Foundational is done.

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 (setup) + Phase 2 (foundational).
2. Phase 3 (US1) — define + apply + record.
3. STOP and validate: two migrations apply once.
4. Ship/demo as MVP.

### Incremental Delivery

US1 → US2 (status) → US3 (rollback) → US4 (CLI) → Polish (coverage/CI). Each story is independently testable.

## Notes

- Repositories stay bus-agnostic (natural types only); handlers are the only code that touches repositories and adapt output into read-models.
- No php-db `ResultSet`/`RowPrototype` crosses the bus boundary.
- Commit after each task or logical group; squash-merge PRs to `0.1.x`.
- Commit author MUST be `Joey Smith <jsmith@webinertia.net>`.
