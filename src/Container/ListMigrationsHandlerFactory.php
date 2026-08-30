<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\QueryHandler\ListMigrationsHandler;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;

final readonly class ListMigrationsHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ListMigrationsHandler
    {
        return new ListMigrationsHandler(
            $container->get(MigrationDiscovery::class),
            $container->get(MigrationRepositoryInterface::class),
            $container->get(MigrationChecksum::class),
        );
    }
}
