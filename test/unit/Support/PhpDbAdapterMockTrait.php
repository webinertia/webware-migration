<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Support;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

use function array_fill;
use function count;

/**
 * Builds a PhpDb adapter mock that supports the Sql->buildSqlString (DDL),
 * Sql->prepareStatementForSqlObject (DML), and Adapter->executeQuery paths used
 * by PhpDbMigrationRepository.
 */
trait PhpDbAdapterMockTrait
{
    /** @var list<PreparableSqlInterface> */
    protected array $preparedSqlObjects = [];

    /** @var list<string> */
    protected array $builtSqlStrings = [];

    /**
     * @param list<ResultInterface> $results
     */
    protected function createAdapter(array $results = []): AdapterInterface
    {
        $this->preparedSqlObjects = [];
        $this->builtSqlStrings    = [];

        $adapter    = $this->createStub(AdapterInterface::class);
        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $platform   = $this->createStub(PlatformInterface::class);
        $decorator  = $this->createStubForIntersectionOfInterfaces([
            PlatformDecoratorInterface::class,
            PreparableSqlInterface::class,
            SqlInterface::class,
        ]);

        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn($platform);
        $driver->method('getConnection')->willReturn($connection);
        $connection->method('beginTransaction')->willReturnSelf();
        $connection->method('commit')->willReturnSelf();
        $connection->method('rollback')->willReturnSelf();
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);

        $decorator->method('setSubject')
            ->willReturnCallback(
                function (SqlInterface|PreparableSqlInterface|null $subject) use (
                    $decorator,
                ): PlatformDecoratorInterface {
                    if ($subject instanceof PreparableSqlInterface) {
                        $this->preparedSqlObjects[] = $subject;
                    }

                    return $decorator;
                },
            );
        $decorator->method('getSqlString')->willReturn('CREATE TABLE schema_migrations (...)');
        $decorator->method('prepareStatement')->willReturnArgument(1);

        $driver->method('createStatement')->willReturn($this->createStub(StatementInterface::class));

        if ([] !== $results) {
            $adapter->method('executeQuery')->willReturnOnConsecutiveCalls(...$results);
        }

        return $adapter;
    }

    protected function createAdapterWithConnection(ConnectionInterface $connection): AdapterInterface
    {
        $adapter   = $this->createStub(AdapterInterface::class);
        $driver    = $this->createStub(DriverInterface::class);
        $platform  = $this->createStub(PlatformInterface::class);
        $decorator = $this->createStubForIntersectionOfInterfaces([
            PlatformDecoratorInterface::class,
            PreparableSqlInterface::class,
            SqlInterface::class,
        ]);

        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn($platform);
        $driver->method('getConnection')->willReturn($connection);
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);

        $decorator->method('setSubject')->willReturn($decorator);
        $decorator->method('getSqlString')->willReturn('CREATE TABLE schema_migrations (...)');
        $decorator->method('prepareStatement')->willReturnArgument(1);

        $driver->method('createStatement')->willReturn($this->createStub(StatementInterface::class));

        return $adapter;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    protected function createResult(array $rows): ResultInterface
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('valid')
            ->willReturnOnConsecutiveCalls(...[...array_fill(0, count($rows), value: true), false]);
        if ([] !== $rows) {
            $result->method('current')->willReturnOnConsecutiveCalls(...$rows);
        }

        return $result;
    }
}
