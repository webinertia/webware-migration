<?php

declare(strict_types=1);

namespace Webware\Migration\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Lists discovered migrations with their applied/pending state (FR-005).
 */
final readonly class ListMigrationsQuery implements QueryInterface {}
