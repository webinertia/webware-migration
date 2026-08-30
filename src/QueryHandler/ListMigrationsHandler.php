<?php

declare(strict_types=1);

namespace Webware\Migration\QueryHandler;

use PhpDb\Exception\ExceptionInterface as PhpDbException;
use ReflectionException;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\Migration\Exception\ChecksumMismatchException;
use Webware\Migration\Exception\DuplicateVersionException;
use Webware\Migration\Query\ListMigrationsQuery;
use Webware\Migration\ReadModel\MigrationInfo;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;

/**
 * Adapts discovered migrations and applied records into MigrationInfo read
 * models. Throws on checksum mismatch (queries have no error channel).
 */
final readonly class ListMigrationsHandler implements QueryHandlerInterface
{
    public function __construct(
        private MigrationDiscovery $discovery,
        private MigrationRepositoryInterface $repository,
        private MigrationChecksum $checksum,
    ) {}

    /**
     * @return QueryResult
     * @throws ChecksumMismatchException
     * @throws DuplicateVersionException
     * @throws PhpDbException
     * @throws ReflectionException
     */
    public function handle(ListMigrationsQuery $query): QueryResult
    {
        $applied = [];
        foreach ($this->repository->fetchApplied() as $record) {
            $applied[$record['version']] = $record;
        }

        $infos = [];
        foreach ($this->discovery->discover() as $migration) {
            $record = $applied[$migration->getVersion()] ?? null;

            if (null !== $record) {
                if ($record['checksum'] !== $this->checksum->compute($migration)) {
                    throw ChecksumMismatchException::forVersion($migration->getVersion());
                }

                $infos[] = new MigrationInfo(
                    version    : $migration->getVersion(),
                    description: $migration->getDescription(),
                    status     : MigrationInfo::STATUS_APPLIED,
                );

                continue;
            }

            $infos[] = new MigrationInfo(
                version    : $migration->getVersion(),
                description: $migration->getDescription(),
                status     : MigrationInfo::STATUS_PENDING,
            );
        }

        return new QueryResult(
            query : $query,
            status: MessageStatus::Success,
            result: $infos,
        );
    }
}
