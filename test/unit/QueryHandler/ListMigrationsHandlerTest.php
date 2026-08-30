<?php

declare(strict_types=1);

namespace WebwareTest\Migration\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Exception\ChecksumMismatchException;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Query\ListMigrationsQuery;
use Webware\Migration\QueryHandler\ListMigrationsHandler;
use Webware\Migration\ReadModel\MigrationInfo;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;

use function file_get_contents;
use function hash;

#[CoversClass(ListMigrationsHandler::class)]
#[CoversMethod(ListMigrationsHandler::class, '__construct')]
#[CoversMethod(ListMigrationsHandler::class, 'handle')]
final class ListMigrationsHandlerTest extends TestCase
{
    #[Test]
    public function listsAppliedAndPendingMigrations(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('fetchApplied')->willReturn([$this->appliedRecord($first)]);

        $handler = new ListMigrationsHandler(
            discovery : new MigrationDiscovery(migrations: [$second, $first]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $result = $handler->handle(new ListMigrationsQuery());

        static::assertSame(
            expected: MessageStatus::Success,
            actual  : $result->getStatus(),
        );

        /** @var list<MigrationInfo> $infos */
        $infos = $result->getResult();

        static::assertCount(
            expectedCount: 2,
            haystack     : $infos,
        );
        static::assertInstanceOf(
            expected: MigrationInfo::class,
            actual  : $infos[0],
        );
        static::assertSame(
            expected: 1,
            actual  : $infos[0]->version,
        );
        static::assertSame(
            expected: 'Create Roles',
            actual  : $infos[0]->description,
        );
        static::assertSame(
            expected: MigrationInfo::STATUS_APPLIED,
            actual  : $infos[0]->status,
        );
        static::assertSame(
            expected: 2,
            actual  : $infos[1]->version,
        );
        static::assertSame(
            expected: MigrationInfo::STATUS_PENDING,
            actual  : $infos[1]->status,
        );
    }

    #[Test]
    public function returnsEmptyListWhenNoMigrations(): void
    {
        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('fetchApplied')->willReturn([]);

        $handler = new ListMigrationsHandler(
            discovery : new MigrationDiscovery(migrations: []),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $result = $handler->handle(new ListMigrationsQuery());

        static::assertSame(
            expected: [],
            actual  : $result->getResult(),
        );
    }

    #[Test]
    public function throwsOnChecksumMismatch(): void
    {
        $first = new Migration001CreateRoles();

        $record             = $this->appliedRecord($first);
        $record['checksum'] = 'corrupted-checksum';

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('fetchApplied')->willReturn([$record]);

        $handler = new ListMigrationsHandler(
            discovery : new MigrationDiscovery(migrations: [$first]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $this->expectException(exception: ChecksumMismatchException::class);

        $handler->handle(new ListMigrationsQuery());
    }

    /**
     * @return array{version: int, description: string, applied_at: string, checksum: string}
     */
    private function appliedRecord(MigrationInterface $migration): array
    {
        $file = (string) new ReflectionClass(objectOrClass: $migration)->getFileName();

        return [
            'version'     => $migration->getVersion(),
            'description' => $migration->getDescription(),
            'applied_at'  => '2026-01-01 00:00:00',
            'checksum'    => hash(
                algo: 'sha256',
                data: (string) file_get_contents(filename: $file),
            ),
        ];
    }
}
