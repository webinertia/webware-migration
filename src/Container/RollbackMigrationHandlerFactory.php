<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\CommandHandler\RollbackMigrationHandler;
use Webware\Migration\Runner\MigrationRunner;

final readonly class RollbackMigrationHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RollbackMigrationHandler
    {
        return new RollbackMigrationHandler($container->get(MigrationRunner::class));
    }
}
