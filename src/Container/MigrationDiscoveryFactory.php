<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Runner\MigrationDiscovery;

/**
 * Builds the discovery from the configured migration service list.
 */
final readonly class MigrationDiscoveryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigrationDiscovery
    {
        /** @var array<string, mixed> $config */
        $config = $container->get(id: 'config');

        /** @var list<string> $migrationConfig */
        $migrationConfig = $config[MigrationInterface::class] ?? [];

        $migrations = [];
        foreach ($migrationConfig as $service) {
            /** @var MigrationInterface $migration */
            $migration = $container->get(id: $service);

            $migrations[] = $migration;
        }

        return new MigrationDiscovery($migrations);
    }
}
