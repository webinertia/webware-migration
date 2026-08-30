<?php

declare(strict_types=1);

namespace Webware\Migration\Command;

use Override;
use Webware\MessageBus\Command\NamedCommandInterface;

/**
 * Applies every pending migration in ascending version order (FR-003).
 */
final readonly class RunMigrationsCommand implements NamedCommandInterface
{
    #[Override]
    public function getName(): string
    {
        return self::class;
    }
}
