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
use Webware\Migration\Command\RunMigrationsCommand;
use Webware\Migration\Console\MigrateCommand;

#[CoversClass(MigrateCommand::class)]
#[CoversMethod(MigrateCommand::class, '__construct')]
#[CoversMethod(MigrateCommand::class, 'execute')]
final class MigrateCommandTest extends TestCase
{
    #[Test]
    public function printsAppliedVersionsAndReturnsSuccess(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new CommandResult(
            command: new RunMigrationsCommand(),
            status : MessageStatus::Success,
            result : [1, 2],
        ));

        $tester = new CommandTester(new MigrateCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 0,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Applied migration 1.',
            haystack: $tester->getDisplay(),
        );
        static::assertStringContainsString(
            needle  : 'Applied migration 2.',
            haystack: $tester->getDisplay(),
        );
    }

    #[Test]
    public function reportsFailureAndReturnsFailureStatus(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new CommandResult(
            command: new RunMigrationsCommand(),
            status : MessageStatus::Failure,
            result : new RuntimeException('boom'),
        ));

        $tester = new CommandTester(new MigrateCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 1,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Migration failed: boom',
            haystack: $tester->getDisplay(),
        );
    }

    #[Test]
    public function reportsUpToDateWhenNothingApplied(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new CommandResult(
            command: new RunMigrationsCommand(),
            status : MessageStatus::Success,
            result : [],
        ));

        $tester = new CommandTester(new MigrateCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 0,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Already up to date.',
            haystack: $tester->getDisplay(),
        );
    }
}
