<?php

declare(strict_types=1);

namespace Webware\Migration\Console;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RollbackMigrationCommand;

use function sprintf;

/**
 * Reverts the N most recently applied migrations in reverse order (FR-006).
 */
#[AsCommand(name: 'rollback', description: 'Revert the most recently applied migrations')]
final class RollbackCommand extends Command
{
    /**
     * @throws ConsoleExceptionInterface
     */
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    /**
     * @throws ConsoleExceptionInterface
     */
    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            name       : 'steps',
            shortcut   : null,
            mode       : InputOption::VALUE_REQUIRED,
            description: 'Number of migrations to revert',
            default    : 1,
        );
    }

    /**
     * @throws ConsoleExceptionInterface
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $steps = (int) $input->getOption('steps');

        $result = $this->bus->handle(new RollbackMigrationCommand(steps: $steps));

        if (MessageStatus::Success !== $result->getStatus()) {
            /** @var \Throwable $error */
            $error = $result->getResult();
            $output->writeln(sprintf('<error>Rollback failed: %s</error>', $error->getMessage()));

            return Command::FAILURE;
        }

        /** @var list<int> $reverted */
        $reverted = $result->getResult();

        if ([] === $reverted) {
            $output->writeln('Nothing to roll back.');
        }

        foreach ($reverted as $version) {
            $output->writeln(sprintf('Reverted migration %d.', $version));
        }

        return Command::SUCCESS;
    }
}
