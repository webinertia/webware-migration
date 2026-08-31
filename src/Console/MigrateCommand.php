<?php

declare(strict_types=1);

namespace Webware\Migration\Console;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\Migration\Command\RunMigrationsCommand;

use function sprintf;

/**
 * Applies every pending migration in ascending version order (FR-003).
 */
#[AsCommand(name: 'migrate', description: 'Apply pending migrations in order')]
final class MigrateCommand extends Command
{
    /**
     * @throws ConsoleExceptionInterface
     */
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->bus->handle(new RunMigrationsCommand());

        if (MessageStatus::Success !== $result->getStatus()) {
            /** @var \Throwable $error */
            $error = $result->getResult();
            $output->writeln(sprintf('<error>Migration failed: %s</error>', $error->getMessage()));

            return Command::FAILURE;
        }

        /** @var list<int> $applied */
        $applied = $result->getResult();

        if ([] === $applied) {
            $output->writeln('Already up to date.');
        }

        foreach ($applied as $version) {
            $output->writeln(sprintf('Applied migration %d.', $version));
        }

        return Command::SUCCESS;
    }
}
