<?php

declare(strict_types=1);

namespace Webware\Migration\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Reverts the N most recently applied migrations in reverse order (FR-006).
 */
final class RollbackMigrationCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public readonly int $steps = 1,
    ) {}
}
