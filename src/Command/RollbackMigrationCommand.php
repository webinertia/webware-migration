<?php

declare(strict_types=1);

namespace Webware\Migration\Command;

use Override;
use Webware\MessageBus\Command\NamedCommandInterface;

/**
 * Reverts the N most recently applied migrations in reverse order (FR-006).
 */
final readonly class RollbackMigrationCommand implements NamedCommandInterface
{
    public function __construct(
        public int $steps = 1,
    ) {}

    #[Override]
    public function getName(): string
    {
        return self::class;
    }
}
