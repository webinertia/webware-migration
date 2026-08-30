<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Repository;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Migration\Repository\PhpDbMigrationRepository;
use WebwareTest\Migration\Support\PhpDbAdapterMockTrait;

#[CoversClass(PhpDbMigrationRepository::class)]
#[CoversMethod(PhpDbMigrationRepository::class, '__construct')]
#[CoversMethod(PhpDbMigrationRepository::class, 'fetchApplied')]
#[CoversMethod(PhpDbMigrationRepository::class, 'findApplied')]
#[CoversMethod(PhpDbMigrationRepository::class, 'recordApplied')]
#[CoversMethod(PhpDbMigrationRepository::class, 'removeApplied')]
#[CoversMethod(PhpDbMigrationRepository::class, 'transactional')]
final class PhpDbMigrationRepositoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function fetchAppliedHydratesRowsSkippingNonArrays(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: [
                null,
                [
                    'version'     => 1,
                    'description' => 'Create Roles',
                    'applied_at'  => '2026-01-01 00:00:00',
                    'checksum'    => 'abc123',
                ],
            ]),
        ]));

        static::assertSame(
            expected: [
                [
                    'version'     => 1,
                    'description' => 'Create Roles',
                    'applied_at'  => '2026-01-01 00:00:00',
                    'checksum'    => 'abc123',
                ],
            ],
            actual  : $repository->fetchApplied(),
        );
    }

    #[Test]
    public function fetchAppliedUsesDefaultsForMissingValues(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: [['version' => null]]),
        ]));

        static::assertSame(
            expected: [[
                'version'     => 0,
                'description' => '',
                'applied_at'  => '',
                'checksum'    => '',
            ]],
            actual  : $repository->fetchApplied(),
        );
    }

    #[Test]
    public function findAppliedReturnsHydratedRecordWhenFound(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: [[
                'version'     => '7',
                'description' => 'Seed Data',
                'applied_at'  => '2026-02-02 00:00:00',
                'checksum'    => 'deadbeef',
            ]]),
        ]));

        static::assertSame(
            expected: [
                'version'     => 7,
                'description' => 'Seed Data',
                'applied_at'  => '2026-02-02 00:00:00',
                'checksum'    => 'deadbeef',
            ],
            actual  : $repository->findApplied(version: 7),
        );
    }

    #[Test]
    public function findAppliedReturnsNullWhenNotFound(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: []),
        ]));

        static::assertNull(actual: $repository->findApplied(version: 99));
    }

    #[Test]
    public function findAppliedSkipsNonArrayRows(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: [
                null,
                [
                    'version'     => 7,
                    'description' => 'Seed Data',
                    'applied_at'  => '2026-02-02 00:00:00',
                    'checksum'    => 'deadbeef',
                ],
            ]),
        ]));

        static::assertSame(
            expected: [
                'version'     => 7,
                'description' => 'Seed Data',
                'applied_at'  => '2026-02-02 00:00:00',
                'checksum'    => 'deadbeef',
            ],
            actual  : $repository->findApplied(version: 7),
        );
    }

    #[Test]
    public function recordAppliedExecutesInsert(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: []),
        ]));

        $repository->recordApplied(
            version    : 1,
            description: 'Create Roles',
            checksum   : 'abc123',
        );

        static::assertCount(
            expectedCount: 1,
            haystack     : $this->preparedSqlObjects,
        );
    }

    #[Test]
    public function removeAppliedExecutesDelete(): void
    {
        $repository = new PhpDbMigrationRepository(adapter: $this->createAdapter(results: [
            $this->createResult(rows: []),
        ]));

        $repository->removeApplied(version: 1);

        static::assertCount(
            expectedCount: 1,
            haystack     : $this->preparedSqlObjects,
        );
    }

    #[Test]
    public function transactionalCommitsWhenOperationSucceeds(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(static::once())->method('beginTransaction')->willReturnSelf();
        $connection->expects(static::once())->method('commit')->willReturnSelf();
        $connection->expects(static::never())->method('rollback');

        $repository = new PhpDbMigrationRepository(
            adapter: $this->createAdapterWithConnection($connection),
        );

        $ran = false;
        $repository->transactional(static function () use (&$ran): void {
            $ran = true;
        });

        static::assertTrue(condition: $ran);
    }

    #[Test]
    public function transactionalRollsBackAndRethrowsOnFailure(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(static::once())->method('beginTransaction')->willReturnSelf();
        $connection->expects(static::never())->method('commit');
        $connection->expects(static::once())->method('rollback')->willReturnSelf();

        $repository = new PhpDbMigrationRepository(
            adapter: $this->createAdapterWithConnection($connection),
        );

        $error = new RuntimeException('boom');

        $this->expectException(exception: RuntimeException::class);

        $repository->transactional(static function () use ($error): void {
            throw $error;
        });
    }
}
