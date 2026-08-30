<?php

declare(strict_types=1);

namespace Webware\Migration\Repository;

use Closure;
use Throwable;

/**
 * Persistence seam for applied-migration records.
 *
 * Bus-agnostic: returns natural PHP types only — no php-db or message-bus
 * types cross this boundary (constitution III).
 *
 * @api
 */
interface MigrationRepositoryInterface
{
    /**
     * @return list<array{version: int, description: string, applied_at: string, checksum: string}>
     */
    public function fetchApplied(): array;

    /**
     * @return array{version: int, description: string, applied_at: string, checksum: string}|null
     */
    public function findApplied(int $version): ?array;

    public function recordApplied(int $version, string $description, string $checksum): void;

    public function removeApplied(int $version): void;

    /**
     * Runs the operation inside a transaction; rolls back and rethrows on any
     * failure so a failed step is never recorded (FR-010).
     *
     * @throws Throwable
     */
    public function transactional(Closure $operation): void;
}
