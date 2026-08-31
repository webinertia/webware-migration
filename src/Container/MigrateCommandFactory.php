<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\Migration\Console\MigrateCommand;

/**
 * @internal
 */
final readonly class MigrateCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConsoleExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MigrateCommand
    {
        return new MigrateCommand($container->get(MessageBusInterface::class));
    }
}
