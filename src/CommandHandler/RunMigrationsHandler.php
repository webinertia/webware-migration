<?php

declare(strict_types=1);

namespace Webware\Migration\CommandHandler;

use Throwable;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RunMigrationsCommand;
use Webware\Migration\Runner\MigrationRunner;

final readonly class RunMigrationsHandler implements CommandHandlerInterface
{
    public function __construct(
        private MigrationRunner $runner,
    ) {}

    public function handle(RunMigrationsCommand $command): CommandResultInterface
    {
        try {
            $applied = $this->runner->migrate();
        } catch (Throwable $e) {
            return new CommandResult(
                command: $command,
                status : MessageStatus::Failure,
                result : $e,
            );
        }

        return new CommandResult(
            command: $command,
            status : MessageStatus::Success,
            result : $applied,
        );
    }
}
