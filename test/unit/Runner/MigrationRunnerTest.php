<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Runner;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Webware\Migration\Exception\ChecksumMismatchException;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;
use WebwareTest\Migration\Fixture\Migration998FailOnRevert;
use WebwareTest\Migration\Fixture\Migration999FailOnApply;

use function file_get_contents;
use function hash;

#[CoversClass(MigrationRunner::class)]
#[CoversMethod(MigrationRunner::class, '__construct')]
#[CoversMethod(MigrationRunner::class, 'migrate')]
#[CoversMethod(MigrationRunner::class, 'rollback')]
final class MigrationRunnerTest extends TestCase
{
    #[Test]
    public function migrateAppliesOnlyPendingMigrations(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $applied = [1 => $this->appliedRecord($first)];

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => $applied[$version] ?? null,
            );
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [2],
            actual  : $runner->migrate(),
        );
        static::assertSame(
            expected: [],
            actual  : $first->events,
        );
        static::assertSame(
            expected: ['up'],
            actual  : $second->events,
        );
    }

    #[Test]
    public function migrateAppliesPendingInAscendingOrder(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn(null);
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$second, $first]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [1, 2],
            actual  : $runner->migrate(),
        );
        static::assertSame(
            expected: ['up'],
            actual  : $first->events,
        );
        static::assertSame(
            expected: ['up'],
            actual  : $second->events,
        );
    }

    #[Test]
    public function migrateRethrowsApplyFailureWithoutRecording(): void
    {
        $failing = new Migration999FailOnApply();

        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn(null);
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );
        $repository->expects(static::never())->method('recordApplied');

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$failing]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $this->expectException(exception: RuntimeException::class);

        $runner->migrate();
    }

    #[Test]
    public function migrateSkipsAlreadyAppliedMigrations(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $applied = [
            1 => $this->appliedRecord($first),
            2 => $this->appliedRecord($second),
        ];

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => $applied[$version] ?? null,
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [],
            actual  : $runner->migrate(),
        );
        static::assertSame(
            expected: [],
            actual  : $first->events,
        );
        static::assertSame(
            expected: [],
            actual  : $second->events,
        );
    }

    #[Test]
    public function migrateThrowsOnChecksumMismatch(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $record             = $this->appliedRecord($first);
        $record['checksum'] = 'corrupted-checksum';

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => 1 === $version ? $record : null,
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $this->expectException(exception: ChecksumMismatchException::class);

        $runner->migrate();
    }

    #[Test]
    public function migrateThrowsOnChecksumMismatchAfterPendingMigrations(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $record             = $this->appliedRecord($second);
        $record['checksum'] = 'corrupted-checksum';

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => 2 === $version ? $record : null,
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $this->expectException(exception: ChecksumMismatchException::class);

        $runner->migrate();
    }

    #[Test]
    public function rollbackDefaultsToSingleStep(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $applied = [
            1 => $this->appliedRecord($first),
            2 => $this->appliedRecord($second),
        ];

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => $applied[$version] ?? null,
            );
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [2],
            actual  : $runner->rollback(),
        );
    }

    #[Test]
    public function rollbackRethrowsRevertFailureWithoutRemovingRecord(): void
    {
        $failing = new Migration998FailOnRevert();

        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn($this->appliedRecord($failing));
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );
        $repository->expects(static::never())->method('removeApplied');

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$failing]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $this->expectException(exception: RuntimeException::class);

        $runner->rollback();
    }

    #[Test]
    public function rollbackReturnsEmptyWhenNothingApplied(): void
    {
        $first = new Migration001CreateRoles();

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn(null);

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [],
            actual  : $runner->rollback(),
        );
        static::assertSame(
            expected: [],
            actual  : $first->events,
        );
    }

    #[Test]
    public function rollbackRevertsMostRecentMigrationInReverseOrder(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $applied = [
            1 => $this->appliedRecord($first),
            2 => $this->appliedRecord($second),
        ];

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => $applied[$version] ?? null,
            );
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [2],
            actual  : $runner->rollback(steps: 1),
        );
        static::assertSame(
            expected: ['down'],
            actual  : $second->events,
        );
        static::assertSame(
            expected: [],
            actual  : $first->events,
        );
    }

    #[Test]
    public function rollbackRevertsMultipleStepsInReverseOrder(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $applied = [
            1 => $this->appliedRecord($first),
            2 => $this->appliedRecord($second),
        ];

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => $applied[$version] ?? null,
            );
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [2, 1],
            actual  : $runner->rollback(steps: 2),
        );
        static::assertSame(
            expected: ['down'],
            actual  : $first->events,
        );
        static::assertSame(
            expected: ['down'],
            actual  : $second->events,
        );
    }

    #[Test]
    public function rollbackSkipsUnappliedMigrations(): void
    {
        $first  = new Migration001CreateRoles();
        $second = new Migration002AddRoleColumn();

        $applied = [2 => $this->appliedRecord($second)];

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')
            ->willReturnCallback(
                static fn(int $version): ?array => $applied[$version] ?? null,
            );
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$first, $second]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        static::assertSame(
            expected: [2],
            actual  : $runner->rollback(),
        );
        static::assertSame(
            expected: [],
            actual  : $first->events,
        );
        static::assertSame(
            expected: ['down'],
            actual  : $second->events,
        );
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
