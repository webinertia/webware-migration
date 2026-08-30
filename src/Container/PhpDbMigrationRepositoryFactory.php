<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception\ExceptionInterface as PhpDbException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Migration\Repository\PhpDbMigrationRepository;

final readonly class PhpDbMigrationRepositoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws PhpDbException
     */
    public function __invoke(ContainerInterface $container): PhpDbMigrationRepository
    {
        return new PhpDbMigrationRepository($container->get(AdapterInterface::class));
    }
}
