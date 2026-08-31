<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\Migration\Console\RollbackCommand;

/**
 * @internal
 */
final readonly class RollbackCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConsoleExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RollbackCommand
    {
        return new RollbackCommand($container->get(MessageBusInterface::class));
    }
}
