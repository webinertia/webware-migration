<?php

declare(strict_types=1);

namespace WebwareTest\Migration\CommandHandler;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RollbackMigrationCommand;
use Webware\Migration\CommandHandler\RollbackMigrationHandler;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;
use WebwareTest\Migration\Fixture\Migration998FailOnRevert;

use function file_get_contents;
use function hash;

#[CoversClass(RollbackMigrationHandler::class)]
#[CoversMethod(RollbackMigrationHandler::class, '__construct')]
#[CoversMethod(RollbackMigrationHandler::class, 'handle')]
final class RollbackMigrationHandlerTest extends TestCase
{
    #[Test]
    public function returnsFailureWithExceptionOnError(): void
    {
        $failing = new Migration998FailOnRevert();

        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn($this->appliedRecord($failing));
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [$failing]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $handler = new RollbackMigrationHandler(runner: $runner);

        $result = $handler->handle(new RollbackMigrationCommand());

        static::assertSame(
            expected: MessageStatus::Failure,
            actual  : $result->getStatus(),
        );
        static::assertInstanceOf(
            expected: RuntimeException::class,
            actual  : $result->getResult(),
        );
    }

    #[Test]
    public function returnsSuccessWithRevertedVersions(): void
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

        $handler = new RollbackMigrationHandler(runner: $runner);

        $result = $handler->handle(new RollbackMigrationCommand(steps: 1));

        static::assertSame(
            expected: MessageStatus::Success,
            actual  : $result->getStatus(),
        );
        static::assertSame(
            expected: [2],
            actual  : $result->getResult(),
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
