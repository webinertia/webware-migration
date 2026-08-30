<?php

declare(strict_types=1);

namespace Webware\Migration\QueryHandler;

use PhpDb\Exception\ExceptionInterface as PhpDbException;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\Migration\Query\FetchAppliedMigrationsQuery;
use Webware\Migration\ReadModel\AppliedMigration;
use Webware\Migration\Repository\MigrationRepositoryInterface;

/**
 * Adapts durable applied-migration records into AppliedMigration read models.
 */
final readonly class FetchAppliedMigrationsHandler implements QueryHandlerInterface
{
    public function __construct(
        private MigrationRepositoryInterface $repository,
    ) {}

    /**
     * @throws PhpDbException
     */
    public function handle(FetchAppliedMigrationsQuery $query): QueryResult
    {
        $models = [];
        foreach ($this->repository->fetchApplied() as $record) {
            $models[] = new AppliedMigration(
                version    : $record['version'],
                description: $record['description'],
                appliedAt  : $record['applied_at'],
                checksum   : $record['checksum'],
            );
        }

        return new QueryResult(
            query : $query,
            status: MessageStatus::Success,
            result: $models,
        );
    }
}
