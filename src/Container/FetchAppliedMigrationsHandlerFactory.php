<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\QueryHandler\FetchAppliedMigrationsHandler;
use Webware\Migration\Repository\MigrationRepositoryInterface;

final readonly class FetchAppliedMigrationsHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchAppliedMigrationsHandler
    {
        return new FetchAppliedMigrationsHandler($container->get(MigrationRepositoryInterface::class));
    }
}
