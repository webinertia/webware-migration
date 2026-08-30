<?php

declare(strict_types=1);

namespace Webware\Migration;

/**
 * A versioned unit of change with an apply step and a revert step.
 *
 * @api
 */
interface MigrationInterface
{
    /**
     * Reverts the change.
     *
     * MUST throw on failure so the runner does not remove the record.
     */
    public function down(): void;

    /**
     * A human-readable summary of the change.
     */
    public function getDescription(): string;

    /**
     * The integer version that determines this migration's ordering.
     */
    public function getVersion(): int;

    /**
     * Applies the change.
     *
     * MUST throw on failure so the runner does not record the migration.
     */
    public function up(): void;
}
