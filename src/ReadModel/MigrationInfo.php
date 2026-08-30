<?php

declare(strict_types=1);

namespace Webware\Migration\ReadModel;

/**
 * Read-only view of a migration's applied/pending state for inspection.
 */
final readonly class MigrationInfo
{
    public const string STATUS_APPLIED = 'applied';

    public const string STATUS_PENDING = 'pending';

    public function __construct(
        public int $version,
        public string $description,
        public string $status,
    ) {}
}
