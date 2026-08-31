<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Container;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\Migration\CommandHandler\RollbackMigrationHandler;
use Webware\Migration\CommandHandler\RunMigrationsHandler;
use Webware\Migration\Console\MigrateCommand;
use Webware\Migration\Console\RollbackCommand;
use Webware\Migration\Console\StatusCommand;
use Webware\Migration\Container\FetchAppliedMigrationsHandlerFactory;
use Webware\Migration\Container\ListMigrationsHandlerFactory;
use Webware\Migration\Container\MigrateCommandFactory;
use Webware\Migration\Container\MigrationDiscoveryFactory;
use Webware\Migration\Container\MigrationRunnerFactory;
use Webware\Migration\Container\PhpDbMigrationRepositoryFactory;
use Webware\Migration\Container\RollbackCommandFactory;
use Webware\Migration\Container\RollbackMigrationHandlerFactory;
use Webware\Migration\Container\RunMigrationsHandlerFactory;
use Webware\Migration\Container\StatusCommandFactory;
use Webware\Migration\MigrationInterface;
use Webware\Migration\QueryHandler\FetchAppliedMigrationsHandler;
use Webware\Migration\QueryHandler\ListMigrationsHandler;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Repository\PhpDbMigrationRepository;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Support\PhpDbAdapterMockTrait;

use function array_map;

#[CoversClass(MigrationDiscoveryFactory::class)]
#[CoversClass(MigrationRunnerFactory::class)]
#[CoversClass(PhpDbMigrationRepositoryFactory::class)]
#[CoversClass(RunMigrationsHandlerFactory::class)]
#[CoversClass(RollbackMigrationHandlerFactory::class)]
#[CoversClass(ListMigrationsHandlerFactory::class)]
#[CoversClass(FetchAppliedMigrationsHandlerFactory::class)]
#[CoversClass(MigrateCommandFactory::class)]
#[CoversClass(RollbackCommandFactory::class)]
#[CoversClass(StatusCommandFactory::class)]
#[CoversMethod(MigrationDiscoveryFactory::class, '__invoke')]
#[CoversMethod(MigrationRunnerFactory::class, '__invoke')]
#[CoversMethod(PhpDbMigrationRepositoryFactory::class, '__invoke')]
#[CoversMethod(RunMigrationsHandlerFactory::class, '__invoke')]
#[CoversMethod(RollbackMigrationHandlerFactory::class, '__invoke')]
#[CoversMethod(ListMigrationsHandlerFactory::class, '__invoke')]
#[CoversMethod(FetchAppliedMigrationsHandlerFactory::class, '__invoke')]
#[CoversMethod(MigrateCommandFactory::class, '__invoke')]
#[CoversMethod(RollbackCommandFactory::class, '__invoke')]
#[CoversMethod(StatusCommandFactory::class, '__invoke')]
final class ContainerFactoriesTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function fetchAppliedMigrationsHandlerFactoryWiresRepository(): void
    {
        $container = $this->containerStub(map: [
            MigrationRepositoryInterface::class => $this->createStub(MigrationRepositoryInterface::class),
        ]);

        $handler = (new FetchAppliedMigrationsHandlerFactory())($container);

        static::assertInstanceOf(
            expected: FetchAppliedMigrationsHandler::class,
            actual  : $handler,
        );
    }

    #[Test]
    public function listMigrationsHandlerFactoryWiresDependencies(): void
    {
        $container = $this->containerStub(map: [
            MigrationDiscovery::class           => new MigrationDiscovery(migrations: []),
            MigrationRepositoryInterface::class => $this->createStub(MigrationRepositoryInterface::class),
            MigrationChecksum::class            => new MigrationChecksum(),
        ]);

        $handler = (new ListMigrationsHandlerFactory())($container);

        static::assertInstanceOf(
            expected: ListMigrationsHandler::class,
            actual  : $handler,
        );
    }

    #[Test]
    public function migrateCommandFactoryWiresBus(): void
    {
        $container = $this->containerStub(map: [
            MessageBusInterface::class => $this->createStub(MessageBusInterface::class),
        ]);

        $command = (new MigrateCommandFactory())($container);

        static::assertInstanceOf(
            expected: MigrateCommand::class,
            actual  : $command,
        );
    }

    #[Test]
    public function migrationDiscoveryFactoryBuildsEmptyWhenConfigMissing(): void
    {
        $container = $this->containerStub(map: ['config' => []]);

        $discovery = (new MigrationDiscoveryFactory())($container);

        static::assertSame(
            expected: [],
            actual  : $discovery->discover(),
        );
    }

    #[Test]
    public function migrationDiscoveryFactoryBuildsFromConfiguredServices(): void
    {
        $container = $this->containerStub(map: [
            'config'                       => [
                MigrationDiscovery::class => [
                    'migrations' => [Migration001CreateRoles::class],
                ],
            ],
            Migration001CreateRoles::class => new Migration001CreateRoles(),
        ]);

        $discovery = (new MigrationDiscoveryFactory())($container);

        $versions = array_map(
            static fn(MigrationInterface $migration): int => $migration->getVersion(),
            $discovery->discover(),
        );

        static::assertSame(
            expected: [1],
            actual  : $versions,
        );
    }

    #[Test]
    public function migrationRunnerFactoryWiresDependencies(): void
    {
        $container = $this->containerStub(map: [
            MigrationDiscovery::class           => new MigrationDiscovery(migrations: []),
            MigrationRepositoryInterface::class => $this->createStub(MigrationRepositoryInterface::class),
            MigrationChecksum::class            => new MigrationChecksum(),
        ]);

        $runner = (new MigrationRunnerFactory())($container);

        static::assertInstanceOf(
            expected: MigrationRunner::class,
            actual  : $runner,
        );
    }

    #[Test]
    public function phpDbMigrationRepositoryFactoryBuildsRepository(): void
    {
        $container = $this->containerStub(map: [
            AdapterInterface::class => $this->createAdapter(),
        ]);

        $repository = (new PhpDbMigrationRepositoryFactory())($container);

        static::assertInstanceOf(
            expected: PhpDbMigrationRepository::class,
            actual  : $repository,
        );
    }

    #[Test]
    public function rollbackCommandFactoryWiresBus(): void
    {
        $container = $this->containerStub(map: [
            MessageBusInterface::class => $this->createStub(MessageBusInterface::class),
        ]);

        $command = (new RollbackCommandFactory())($container);

        static::assertInstanceOf(
            expected: RollbackCommand::class,
            actual  : $command,
        );
    }

    #[Test]
    public function rollbackMigrationHandlerFactoryWiresRunner(): void
    {
        $container = $this->containerStub(map: [
            MigrationRunner::class => $this->realRunner(),
        ]);

        $handler = (new RollbackMigrationHandlerFactory())($container);

        static::assertInstanceOf(
            expected: RollbackMigrationHandler::class,
            actual  : $handler,
        );
    }

    #[Test]
    public function runMigrationsHandlerFactoryWiresRunner(): void
    {
        $container = $this->containerStub(map: [
            MigrationRunner::class => $this->realRunner(),
        ]);

        $handler = (new RunMigrationsHandlerFactory())($container);

        static::assertInstanceOf(
            expected: RunMigrationsHandler::class,
            actual  : $handler,
        );
    }

    #[Test]
    public function statusCommandFactoryWiresBus(): void
    {
        $container = $this->containerStub(map: [
            MessageBusInterface::class => $this->createStub(MessageBusInterface::class),
        ]);

        $command = (new StatusCommandFactory())($container);

        static::assertInstanceOf(
            expected: StatusCommand::class,
            actual  : $command,
        );
    }

    /**
     * @param array<string, mixed> $map
     */
    private function containerStub(array $map): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => $map[$id] ?? null,
            );

        return $container;
    }

    private function realRunner(): MigrationRunner
    {
        return new MigrationRunner(
            discovery : new MigrationDiscovery(migrations: []),
            repository: $this->createStub(MigrationRepositoryInterface::class),
            checksum  : new MigrationChecksum(),
        );
    }
}
