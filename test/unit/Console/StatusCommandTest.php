<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\Migration\Console\StatusCommand;
use Webware\Migration\Query\ListMigrationsQuery;
use Webware\Migration\ReadModel\MigrationInfo;

#[CoversClass(StatusCommand::class)]
#[CoversMethod(StatusCommand::class, '__construct')]
#[CoversMethod(StatusCommand::class, 'execute')]
final class StatusCommandTest extends TestCase
{
    #[Test]
    public function printsAppliedAndPendingMigrations(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn(new QueryResult(
            query : new ListMigrationsQuery(),
            status: MessageStatus::Success,
            result: [
                new MigrationInfo(
                    version    : 1,
                    description: 'Create Roles',
                    status     : MigrationInfo::STATUS_APPLIED,
                ),
                new MigrationInfo(
                    version    : 2,
                    description: 'Add Role Column',
                    status     : MigrationInfo::STATUS_PENDING,
                ),
            ],
        ));

        $tester = new CommandTester(new StatusCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 0,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'applied 1 Create Roles',
            haystack: $tester->getDisplay(),
        );
        static::assertStringContainsString(
            needle  : 'pending 2 Add Role Column',
            haystack: $tester->getDisplay(),
        );
    }

    #[Test]
    public function reportsFailureOnChecksumMismatch(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willThrowException(
            new RuntimeException('Checksum mismatch for applied migration version 1.'),
        );

        $tester = new CommandTester(new StatusCommand($bus));
        $status = $tester->execute([]);

        static::assertSame(
            expected: 1,
            actual  : $status,
        );
        static::assertStringContainsString(
            needle  : 'Checksum mismatch for applied migration version 1.',
            haystack: $tester->getDisplay(),
        );
    }
}
