<?php

declare(strict_types=1);

namespace WebwareTest\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Console\ConsoleInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\Migration\Command\RollbackMigrationCommand;
use Webware\Migration\Command\RunMigrationsCommand;
use Webware\Migration\CommandHandler\RollbackMigrationHandler;
use Webware\Migration\CommandHandler\RunMigrationsHandler;
use Webware\Migration\ConfigProvider;
use Webware\Migration\Console\MigrateCommand;
use Webware\Migration\Console\RollbackCommand;
use Webware\Migration\Console\StatusCommand;
use Webware\Migration\Container\MigrateCommandFactory;
use Webware\Migration\Container\MigrationDiscoveryFactory;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Query\FetchAppliedMigrationsQuery;
use Webware\Migration\Query\ListMigrationsQuery;
use Webware\Migration\QueryHandler\FetchAppliedMigrationsHandler;
use Webware\Migration\QueryHandler\ListMigrationsHandler;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Repository\PhpDbMigrationRepository;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
#[CoversMethod(ConfigProvider::class, 'getBusConfig')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
#[CoversMethod(ConfigProvider::class, 'getConsoleConfig')]
#[CoversMethod(ConfigProvider::class, 'getMigrationConfig')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function getBusConfigWiresCommandsQueriesAndMiddleware(): void
    {
        $busConfig = new ConfigProvider()->getBusConfig();

        static::assertSame(
            expected: [
                RunMigrationsCommand::class     => RunMigrationsHandler::class,
                RollbackMigrationCommand::class => RollbackMigrationHandler::class,
            ],
            actual  : $busConfig[BusProvider::COMMAND_MAP_KEY],
        );
        static::assertSame(
            expected: [
                ListMigrationsQuery::class         => ListMigrationsHandler::class,
                FetchAppliedMigrationsQuery::class => FetchAppliedMigrationsHandler::class,
            ],
            actual  : $busConfig[BusProvider::QUERY_MAP_KEY],
        );
        static::assertCount(
            expectedCount: 1,
            haystack     : $busConfig[BusProvider::MIDDLEWARE_PIPELINE_KEY],
        );
        static::assertSame(
            expected: MessageHandlerMiddleware::class,
            actual  : $busConfig[BusProvider::MIDDLEWARE_PIPELINE_KEY][0]['middleware'],
        );
    }

    #[Test]
    public function getConsoleConfigRegistersCommands(): void
    {
        static::assertSame(
            expected: [
                'migrate'  => MigrateCommand::class,
                'rollback' => RollbackCommand::class,
                'status'   => StatusCommand::class,
            ],
            actual  : new ConfigProvider()->getConsoleConfig()['commands'],
        );
    }

    #[Test]
    public function getDependenciesWiresAliasesFactoriesAndInvokables(): void
    {
        $dependencies = new ConfigProvider()->getDependencies();

        static::assertSame(
            expected: PhpDbMigrationRepository::class,
            actual  : $dependencies['aliases'][MigrationRepositoryInterface::class],
        );
        static::assertSame(
            expected: MigrationDiscoveryFactory::class,
            actual  : $dependencies['factories'][MigrationDiscovery::class],
        );
        static::assertSame(
            expected: MigrateCommandFactory::class,
            actual  : $dependencies['factories'][MigrateCommand::class],
        );
        static::assertCount(
            expectedCount: 10,
            haystack     : $dependencies['factories'],
        );
        static::assertSame(
            expected: MigrationChecksum::class,
            actual  : $dependencies['invokables'][MigrationChecksum::class],
        );
    }

    #[Test]
    public function getMigrationConfigDefaultsToEmptyList(): void
    {
        static::assertSame(
            expected: [],
            actual  : new ConfigProvider()->getMigrationConfig(),
        );
    }

    #[Test]
    public function invokeMergesAllSections(): void
    {
        $config = (new ConfigProvider())();

        static::assertArrayHasKey(
            key  : 'dependencies',
            array: $config,
        );
        static::assertArrayHasKey(
            key  : ConsoleInterface::class,
            array: $config,
        );
        static::assertArrayHasKey(
            key  : MigrationInterface::class,
            array: $config,
        );
        static::assertArrayHasKey(
            key  : MessageBusInterface::class,
            array: $config,
        );
    }
}
