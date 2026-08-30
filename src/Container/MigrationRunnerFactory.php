<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\Repository\MigrationRepositoryInterface;
use Webware\Migration\Runner\MigrationChecksum;
use Webware\Migration\Runner\MigrationDiscovery;
use Webware\Migration\Runner\MigrationRunner;

final readonly class MigrationRunnerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigrationRunner
    {
        return new MigrationRunner(
            $container->get(MigrationDiscovery::class),
            $container->get(MigrationRepositoryInterface::class),
            $container->get(MigrationChecksum::class),
        );
    }
}
