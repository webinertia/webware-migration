<?php

declare(strict_types=1);

namespace Webware\Migration\Console;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Webware\MessageBus\MessageBusInterface;
use Webware\Migration\Query\ListMigrationsQuery;
use Webware\Migration\ReadModel\MigrationInfo;

use function sprintf;

/**
 * Lists discovered migrations with their applied/pending state (FR-005).
 */
#[AsCommand(name: 'status', description: 'List applied and pending migrations')]
final class StatusCommand extends Command
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
        try {
            $result = $this->bus->handle(new ListMigrationsQuery());
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        /** @var list<MigrationInfo> $infos */
        $infos = $result->getResult();

        foreach ($infos as $info) {
            $output->writeln(sprintf(
                '%s %d %s',
                MigrationInfo::STATUS_APPLIED === $info->status ? 'applied' : 'pending',
                $info->version,
                $info->description,
            ));
        }

        return Command::SUCCESS;
    }
}
