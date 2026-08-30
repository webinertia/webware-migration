<?php

declare(strict_types=1);

namespace WebwareTest\Migration\CommandHandler;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RunMigrationsCommand;
use Webware\Migration\CommandHandler\RunMigrationsHandler;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;
use WebwareTest\Migration\Fixture\Migration999FailOnApply;

#[CoversClass(RunMigrationsHandler::class)]
#[CoversMethod(RunMigrationsHandler::class, '__construct')]
#[CoversMethod(RunMigrationsHandler::class, 'handle')]
final class RunMigrationsHandlerTest extends TestCase
{
    #[Test]
    public function returnsFailureWithExceptionOnError(): void
    {
        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn(null);
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [new Migration999FailOnApply()]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $handler = new RunMigrationsHandler(runner: $runner);

        $result = $handler->handle(new RunMigrationsCommand());

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
    public function returnsSuccessWithAppliedVersions(): void
    {
        $repository = $this->createStub(MigrationRepositoryInterface::class);
        $repository->method('findApplied')->willReturn(null);
        $repository->method('transactional')
            ->willReturnCallback(
                static function (Closure $operation): void {
                    $operation();
                },
            );

        $runner = new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: [
                new Migration001CreateRoles(),
                new Migration002AddRoleColumn(),
            ]),
            repository: $repository,
            checksum  : new MigrationChecksum(),
        );

        $handler = new RunMigrationsHandler(runner: $runner);
        $command = new RunMigrationsCommand();

        $result = $handler->handle($command);

        static::assertSame(
            expected: MessageStatus::Success,
            actual  : $result->getStatus(),
        );
        static::assertSame(
            expected: [1, 2],
            actual  : $result->getResult(),
        );
        static::assertSame(
            expected: $command,
            actual  : $result->getCommand(),
        );
    }
}
