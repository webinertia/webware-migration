<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RollbackMigrationCommand;
use Webware\Migration\Console\RollbackCommand;

#[CoversClass(RollbackCommand::class)]
#[CoversMethod(RollbackCommand::class, '__construct')]
#[CoversMethod(RollbackCommand::class, 'configure')]
#[CoversMethod(RollbackCommand::class, 'execute')]
final class RollbackCommandTest extends TestCase
{
    #[Test]
    public function defaultsToSingleStep(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(static::once())
            ->method('handle')
            ->with(new RollbackMigrationCommand())
            ->willReturn(new CommandResult(
                command: new RollbackMigrationCommand(),
                status : MessageStatus::Success,
                result : [1],
            ));

        $tester = new CommandTester(new RollbackCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 0,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Reverted migration 1.',
            haystack: $tester->getDisplay(),
        );
    }

    #[Test]
    public function reportsFailureAndReturnsFailureStatus(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new CommandResult(
            command: new RollbackMigrationCommand(),
            status : MessageStatus::Failure,
            result : new RuntimeException('boom'),
        ));

        $tester = new CommandTester(new RollbackCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 1,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Rollback failed: boom',
            haystack: $tester->getDisplay(),
        );
    }

    #[Test]
    public function reportsNothingToRollBack(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new CommandResult(
            command: new RollbackMigrationCommand(),
            status : MessageStatus::Success,
            result : [],
        ));

        $tester = new CommandTester(new RollbackCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 0,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Nothing to roll back.',
            haystack: $tester->getDisplay(),
        );
    }

    #[Test]
    public function revertsExplicitStepCount(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(static::once())
            ->method('handle')
            ->with(new RollbackMigrationCommand(steps: 3))
            ->willReturn(new CommandResult(
                command: new RollbackMigrationCommand(steps: 3),
                status : MessageStatus::Success,
                result : [3],
            ));

        $tester = new CommandTester(new RollbackCommand($bus));
        $status = $tester->execute(['--steps' => '3']);

        static::assertSame(
            expected: 0,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Reverted migration 3.',
            haystack: $tester->getDisplay(),
        );
    }
}
