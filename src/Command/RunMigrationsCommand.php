<?php

declare(strict_types=1);

namespace Webware\Migration\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

/**
 * Applies every pending migration in ascending version order (FR-003).
 */
final class RunMigrationsCommand implements NamedCommandInterface
{
    use NamedCommandTrait;
}
