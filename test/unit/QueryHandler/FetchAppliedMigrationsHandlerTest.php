<?php

declare(strict_types=1);

namespace WebwareTest\Migration\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Query\FetchAppliedMigrationsQuery;
use Webware\Migration\QueryHandler\FetchAppliedMigrationsHandler;
use Webware\Migration\ReadModel\AppliedMigration;
use Webware\Migration\Repository\MigrationRepositoryInterface;

#[CoversClass(FetchAppliedMigrationsHandler::class)]
#[CoversMethod(FetchAppliedMigrationsHandler::class, '__construct')]
#[CoversMethod(FetchAppliedMigrationsHandler::class, 'handle')]
final class FetchAppliedMigrationsHandlerTest extends TestCase
{
    #[Test]
    public function mapsRecordsToAppliedMigrationModels(): void
    {
        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('fetchApplied')
            ->willReturn([
                [
                    'version'     => 1,
                    'description' => 'Create Roles',
                    'applied_at'  => '2026-01-01 00:00:00',
                    'checksum'    => 'abc123',
                ],
                [
                    'version'     => 2,
                    'description' => 'Add Role Column',
                    'applied_at'  => '2026-01-02 00:00:00',
                    'checksum'    => 'def456',
                ],
            ]);

        $handler = new FetchAppliedMigrationsHandler(repository: $repository);

        $result = $handler->handle(new FetchAppliedMigrationsQuery());

        static::assertSame(
            expected: MessageStatus::Success,
            actual  : $result->getStatus(),
        );

        /** @var list<AppliedMigration> $models */
        $models = $result->getResult();

        static::assertCount(
            expectedCount: 2,
            haystack     : $models,
        );
        static::assertSame(
            expected: 1,
            actual  : $models[0]->version,
        );
        static::assertSame(
            expected: 'Create Roles',
            actual  : $models[0]->description,
        );
        static::assertSame(
            expected: '2026-01-01 00:00:00',
            actual  : $models[0]->appliedAt,
        );
        static::assertSame(
            expected: 'abc123',
            actual  : $models[0]->checksum,
        );
        static::assertSame(
            expected: 2,
            actual  : $models[1]->version,
        );
        static::assertSame(
            expected: 'def456',
            actual  : $models[1]->checksum,
        );
    }

    #[Test]
    public function returnsEmptyListWhenNoRecords(): void
    {
        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('fetchApplied')->willReturn([]);

        $handler = new FetchAppliedMigrationsHandler(repository: $repository);

        $result = $handler->handle(new FetchAppliedMigrationsQuery());

        static::assertSame(
            expected: [],
            actual  : $result->getResult(),
        );
    }
}
