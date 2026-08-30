<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Migration;

use PhpDb\Adapter\Adapter;
use PhpDb\Sqlite\AdapterPlatform;
use PhpDb\Sqlite\Pdo\Connection;
use PhpDb\Sqlite\Pdo\Driver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Migration\Exception\ChecksumMismatchException;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Repository\PhpDbMigrationRepository;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;

use function array_column;
use function array_keys;
use function str_repeat;
use function strlen;

#[CoversClass(MigrationRunner::class)]
#[CoversClass(PhpDbMigrationRepository::class)]
#[CoversMethod(MigrationRunner::class, 'migrate')]
#[CoversMethod(MigrationRunner::class, 'rollback')]
#[CoversMethod(PhpDbMigrationRepository::class, 'fetchApplied')]
#[CoversMethod(PhpDbMigrationRepository::class, 'recordApplied')]
#[CoversMethod(PhpDbMigrationRepository::class, 'removeApplied')]
final class MigrationRunnerTest extends TestCase
{
    #[Test]
    public function createsSchemaMigrationsTableIdempotently(): void
    {
        $adapter = $this->createAdapter();

        new PhpDbMigrationRepository(adapter: $adapter);

        $columns = $this->fetchTableInfo($adapter);

        static::assertSame(
            expected: ['version', 'description', 'applied_at', 'checksum'],
            actual  : array_keys($columns),
        );
        static::assertSame(
            expected: 'INTEGER',
            actual  : $columns['version']['type'],
        );
        static::assertSame(
            expected: 1,
            actual  : (int) $columns['version']['notnull'],
        );
        static::assertSame(
            expected: 1,
            actual  : (int) $columns['version']['pk'],
        );
        static::assertSame(
            expected: 'VARCHAR(255)',
            actual  : $columns['description']['type'],
        );
        static::assertSame(
            expected: 1,
            actual  : (int) $columns['description']['notnull'],
        );
        static::assertSame(
            expected: 'TIMESTAMP',
            actual  : $columns['applied_at']['type'],
        );
        static::assertSame(
            expected: 1,
            actual  : (int) $columns['applied_at']['notnull'],
        );
        static::assertSame(
            expected: 'VARCHAR(64)',
            actual  : $columns['checksum']['type'],
        );
        static::assertSame(
            expected: 1,
            actual  : (int) $columns['checksum']['notnull'],
        );

        // Creating the table again is a no-op thanks to IF NOT EXISTS.
        new PhpDbMigrationRepository(adapter: $adapter);
    }

    #[Test]
    public function fetchAppliedReturnsHydratedRecords(): void
    {
        $repository = $this->createRepository();
        $runner     = $this->createRunner(
            $repository,
            [new Migration001CreateRoles(), new Migration002AddRoleColumn()],
        );

        $runner->migrate();

        $rows = $repository->fetchApplied();

        static::assertSame(
            expected: [1, 2],
            actual  : array_column($rows, 'version'),
        );
        static::assertSame(
            expected: ['Create Roles', 'Add Role Column'],
            actual  : array_column($rows, 'description'),
        );
        static::assertNotSame(
            expected: '',
            actual  : $rows[0]['applied_at'],
        );
        static::assertNotSame(
            expected: '',
            actual  : $rows[1]['applied_at'],
        );
        static::assertSame(
            expected: 64,
            actual  : strlen($rows[0]['checksum']),
        );
        static::assertSame(
            expected: 64,
            actual  : strlen($rows[1]['checksum']),
        );
    }

    #[Test]
    public function migrateAppliesEachMigrationExactlyOnce(): void
    {
        $repository = $this->createRepository();
        $first      = new Migration001CreateRoles();
        $second     = new Migration002AddRoleColumn();
        $runner     = $this->createRunner($repository, [$first, $second]);

        static::assertSame(
            expected: [1, 2],
            actual  : $runner->migrate(),
        );
        static::assertSame(
            expected: [],
            actual  : $runner->migrate(),
        );
        static::assertSame(
            expected: ['up'],
            actual  : $first->events,
        );
        static::assertCount(
            expectedCount: 2,
            haystack     : $repository->fetchApplied(),
        );
    }

    #[Test]
    public function migrateDetectsChecksumMismatch(): void
    {
        $adapter    = $this->createAdapter();
        $repository = new PhpDbMigrationRepository(adapter: $adapter);
        $first      = new Migration001CreateRoles();
        $second     = new Migration002AddRoleColumn();
        $runner     = $this->createRunner($repository, [$first, $second]);

        $runner->migrate();

        $adapter->getDriver()
            ->getConnection()
            ->execute(
                sql: "UPDATE schema_migrations SET checksum = 'corrupted' WHERE version = 1",
            );

        $this->expectException(exception: ChecksumMismatchException::class);

        $runner->migrate();
    }

    #[Test]
    public function recordAppliedPersistsExplicitVersion(): void
    {
        $repository = $this->createRepository();
        $checksum   = str_repeat(
            string: 'a',
            times : 64,
        );

        $repository->recordApplied(
            version    : 999,
            description: 'Seed Roles',
            checksum   : $checksum,
        );

        $rows = $repository->fetchApplied();

        static::assertCount(
            expectedCount: 1,
            haystack     : $rows,
        );
        static::assertSame(
            expected: 999,
            actual  : $rows[0]['version'],
        );
        static::assertSame(
            expected: 'Seed Roles',
            actual  : $rows[0]['description'],
        );
        static::assertSame(
            expected: $checksum,
            actual  : $rows[0]['checksum'],
        );
    }

    #[Test]
    public function rollbackRevertsMostRecentAppliedMigration(): void
    {
        $repository = $this->createRepository();
        $first      = new Migration001CreateRoles();
        $second     = new Migration002AddRoleColumn();
        $runner     = $this->createRunner($repository, [$first, $second]);

        $runner->migrate();

        static::assertSame(
            expected: [2],
            actual  : $runner->rollback(steps: 1),
        );
        static::assertSame(
            expected: ['up', 'down'],
            actual  : $second->events,
        );
        static::assertSame(
            expected: ['up'],
            actual  : $first->events,
        );
        static::assertCount(
            expectedCount: 1,
            haystack     : $repository->fetchApplied(),
        );
    }

    private function createAdapter(): Adapter
    {
        $connection = new Connection(connectionParameters: ['dsn' => 'sqlite::memory:']);
        $driver     = new Driver(connection: $connection);
        $platform   = new AdapterPlatform(driver: $driver);

        return new Adapter(
            driver  : $driver,
            platform: $platform,
        );
    }

    private function createRepository(): PhpDbMigrationRepository
    {
        return new PhpDbMigrationRepository(adapter: $this->createAdapter());
    }

    /**
     * @param list<MigrationInterface> $migrations
     */
    private function createRunner(PhpDbMigrationRepository $repository, array $migrations): MigrationRunner
    {
        return new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: $migrations),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );
    }

    /**
     * @return array<string, array{name: string, type: string, notnull: int|string, pk: int|string}>
     */
    private function fetchTableInfo(Adapter $adapter): array
    {
        $result  = $adapter->getDriver()->getConnection()->execute('PRAGMA table_info(schema_migrations)');
        $columns = [];

        foreach ($result as $row) {
            $columns[$row['name']] = $row;
        }

        return $columns;
    }
}
