<?php

declare(strict_types=1);

namespace Webware\Migration\ReadModel;

/**
 * Read-only view of a durable applied-migration record.
 */
final readonly class AppliedMigration
{
    public function __construct(
        public int $version,
        public string $description,
        public string $appliedAt,
        public string $checksum,
    ) {}
}
