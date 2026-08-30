<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Migration\Command\RollbackMigrationCommand;
use Webware\Migration\Command\RunMigrationsCommand;

#[CoversClass(RunMigrationsCommand::class)]
#[CoversClass(RollbackMigrationCommand::class)]
#[CoversMethod(RunMigrationsCommand::class, 'getName')]
#[CoversMethod(RollbackMigrationCommand::class, '__construct')]
#[CoversMethod(RollbackMigrationCommand::class, 'getName')]
final class CommandTest extends TestCase
{
    #[Test]
    public function rollbackCommandAcceptsExplicitSteps(): void
    {
        static::assertSame(
            expected: 5,
            actual  : new RollbackMigrationCommand(steps: 5)->steps,
        );
    }

    #[Test]
    public function rollbackCommandDefaultsToSingleStep(): void
    {
        static::assertSame(
            expected: 1,
            actual  : new RollbackMigrationCommand()->steps,
        );
    }

    #[Test]
    public function rollbackCommandNameIsItsClassName(): void
    {
        static::assertSame(
            expected: RollbackMigrationCommand::class,
            actual  : new RollbackMigrationCommand()->getName(),
        );
    }

    #[Test]
    public function runMigrationsCommandNameIsItsClassName(): void
    {
        static::assertSame(
            expected: RunMigrationsCommand::class,
            actual  : new RunMigrationsCommand()->getName(),
        );
    }
}
