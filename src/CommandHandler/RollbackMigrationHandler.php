<?php

declare(strict_types=1);

namespace Webware\Migration\CommandHandler;

use Throwable;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RollbackMigrationCommand;
use Webware\Migration\Runner\MigrationRunner;

final readonly class RollbackMigrationHandler implements CommandHandlerInterface
{
    public function __construct(
        private MigrationRunner $runner,
    ) {}

    public function handle(RollbackMigrationCommand $command): CommandResultInterface
    {
        try {
            $reverted = $this->runner->rollback(steps: $command->steps);
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
            result : $reverted,
        );
    }
}
