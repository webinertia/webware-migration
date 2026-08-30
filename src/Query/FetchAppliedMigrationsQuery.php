<?php

declare(strict_types=1);

namespace Webware\Migration\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetches the durable applied-migration records.
 */
final readonly class FetchAppliedMigrationsQuery implements QueryInterface {}
