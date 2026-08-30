<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\CommandHandler\RunMigrationsHandler;
use Webware\Migration\Runner\MigrationRunner;

final readonly class RunMigrationsHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RunMigrationsHandler
    {
        return new RunMigrationsHandler($container->get(MigrationRunner::class));
    }
}
