<?php

declare(strict_types=1);

namespace Webware\Migration\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\Migration\Console\StatusCommand;

/**
 * @internal
 */
final readonly class StatusCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConsoleExceptionInterface
     */
    public function __invoke(ContainerInterface $container): StatusCommand
    {
        return new StatusCommand($container->get(MessageBusInterface::class));
    }
}
