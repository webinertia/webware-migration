<?php

declare(strict_types=1);

namespace Webware\Migration\Runner;

use Throwable;
use Webware\Migration\Exception\ChecksumMismatchException;
use Webware\Migration\Exception\DuplicateVersionException;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Repository\MigrationRepositoryInterface;

use function array_reverse;
use function array_slice;

/**
 * Applies pending migrations in version order and reverts applied migrations
 * in reverse order (FR-003, FR-006). Never records a migration whose step did
 * not complete successfully (FR-010).
 */
final readonly class MigrationRunner
{
    public function __construct(
        private MigrationDiscovery $discovery,
        private MigrationRepositoryInterface $repository,
        private MigrationChecksum $checksum,
    ) {}

    /**
     * Applies every pending migration in ascending version order.
     *
     * @return list<int> the versions that were applied (empty when up to date)
     * @throws ChecksumMismatchException when an applied migration's source changed
     * @throws DuplicateVersionException
     * @throws Throwable
     */
    public function migrate(): array
    {
        $migrations = $this->discovery->discover();
        $this->assertNoChecksumMismatch($migrations);

        $applied = [];
        foreach ($migrations as $migration) {
            if ($this->repository->findApplied($migration->getVersion()) !== null) {
                continue;
            }

            $checksum = $this->checksum->compute($migration);

            $this->repository->transactional(function () use ($migration, $checksum): void {
                $migration->up();
                $this->repository->recordApplied(
                    version    : $migration->getVersion(),
                    description: $migration->getDescription(),
                    checksum   : $checksum,
                );
            });

            $applied[] = $migration->getVersion();
        }

        return $applied;
    }

    /**
     * Reverts the N most recently applied migrations in reverse order.
     *
     * @return list<int> the versions that were reverted (empty when none applied)
     * @throws DuplicateVersionException
     * @throws Throwable
     */
    public function rollback(int $steps = 1): array
    {
        $migrations = $this->discovery->discover();

        $applied = [];
        foreach ($migrations as $migration) {
            if ($this->repository->findApplied($migration->getVersion()) === null) {
                continue;
            }

            $applied[] = $migration;
        }

        $reverted = [];
        foreach (array_slice(
            array : array_reverse(array: $applied),
            offset: 0,
            length: $steps,
        ) as $migration) {
            $this->repository->transactional(function () use ($migration): void {
                $migration->down();
                $this->repository->removeApplied(version: $migration->getVersion());
            });

            $reverted[] = $migration->getVersion();
        }

        return $reverted;
    }

    /**
     * @param list<MigrationInterface> $migrations
     * @throws ChecksumMismatchException
     * @throws Throwable
     */
    private function assertNoChecksumMismatch(array $migrations): void
    {
        foreach ($migrations as $migration) {
            $record = $this->repository->findApplied($migration->getVersion());

            if (null === $record) {
                continue;
            }

            if ($record['checksum'] !== $this->checksum->compute($migration)) {
                throw ChecksumMismatchException::forVersion($migration->getVersion());
            }
        }
    }
}
