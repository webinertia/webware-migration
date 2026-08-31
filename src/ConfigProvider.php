<?php

declare(strict_types=1);

namespace Webware\Migration;

use Webware\Console\ConsoleInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\Migration\Command\RollbackMigrationCommand;
use Webware\Migration\Command\RunMigrationsCommand;
use Webware\Migration\CommandHandler\RollbackMigrationHandler;
use Webware\Migration\CommandHandler\RunMigrationsHandler;
use Webware\Migration\Console\MigrateCommand;
use Webware\Migration\Console\RollbackCommand;
use Webware\Migration\Console\StatusCommand;
use Webware\Migration\Query\FetchAppliedMigrationsQuery;
use Webware\Migration\Query\ListMigrationsQuery;
use Webware\Migration\QueryHandler\FetchAppliedMigrationsHandler;
use Webware\Migration\QueryHandler\ListMigrationsHandler;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Repository\PhpDbMigrationRepository;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;

/**
 * @type BusConfig = array{
 *   command_map: array<class-string, class-string>,
 *   query_map: array<class-string, class-string>,
 *   middleware_pipeline: array<array{middleware: class-string, priority: int}>,
 * }
 * @type Dependencies = array{
 *   aliases: array<class-string, class-string>,
 *   factories: array<class-string, class-string>,
 *   invokables: array<class-string, class-string>,
 * }
 * @type MigrationConfig = array{migrations: list<string>}
 * @type ConsoleConfig = array{commands: array<string, class-string>}
 * @type ProviderConfig = array{
 *   dependencies: Dependencies,
 *   Webware\Console\ConsoleInterface: ConsoleConfig,
 *   Webware\Migration\Runner\MigrationDiscovery: MigrationConfig,
 *   Webware\MessageBus\MessageBusInterface: BusConfig,
 * }
 * @internal
 */
final class ConfigProvider
{
    /**
     * @return BusConfig
     */
    public function getBusConfig(): array
    {
        return [
            BusProvider::COMMAND_MAP_KEY         => [
                RunMigrationsCommand::class     => RunMigrationsHandler::class,
                RollbackMigrationCommand::class => RollbackMigrationHandler::class,
            ],
            BusProvider::QUERY_MAP_KEY           => [
                ListMigrationsQuery::class         => ListMigrationsHandler::class,
                FetchAppliedMigrationsQuery::class => FetchAppliedMigrationsHandler::class,
            ],
            BusProvider::MIDDLEWARE_PIPELINE_KEY => [
                [
                    'middleware' => MessageHandlerMiddleware::class,
                    'priority'   => BusProvider::DEFAULT_PRIORITY,
                ],
            ],
        ];
    }

    /**
     * @return ConsoleConfig
     */
    public function getConsoleConfig(): array
    {
        return [
            'commands' => [
                'migrate'  => MigrateCommand::class,
                'rollback' => RollbackCommand::class,
                'status'   => StatusCommand::class,
            ],
        ];
    }

    /**
     * @return Dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                MigrationRepositoryInterface::class => PhpDbMigrationRepository::class,
            ],
            'factories'  => [
                PhpDbMigrationRepository::class      => Container\PhpDbMigrationRepositoryFactory::class,
                MigrationDiscovery::class            => Container\MigrationDiscoveryFactory::class,
                MigrationRunner::class               => Container\MigrationRunnerFactory::class,
                RunMigrationsHandler::class          => Container\RunMigrationsHandlerFactory::class,
                RollbackMigrationHandler::class      => Container\RollbackMigrationHandlerFactory::class,
                ListMigrationsHandler::class         => Container\ListMigrationsHandlerFactory::class,
                FetchAppliedMigrationsHandler::class => Container\FetchAppliedMigrationsHandlerFactory::class,
                MigrateCommand::class                => Container\MigrateCommandFactory::class,
                StatusCommand::class                 => Container\StatusCommandFactory::class,
                RollbackCommand::class               => Container\RollbackCommandFactory::class,
            ],
            'invokables' => [
                MigrationChecksum::class => MigrationChecksum::class,
            ],
        ];
    }

    /**
     * @return MigrationConfig
     */
    public function getMigrationConfig(): array
    {
        return [
            'migrations' => [],
        ];
    }

    /**
     * @return ProviderConfig
     */
    public function __invoke(): array
    {
        return [
            'dependencies'             => $this->getDependencies(),
            ConsoleInterface::class    => $this->getConsoleConfig(),
            MigrationDiscovery::class  => $this->getMigrationConfig(),
            MessageBusInterface::class => $this->getBusConfig(),
        ];
    }
}
