<?php

declare(strict_types=1);

namespace Webware\Migration\Runner;

use Webware\Migration\Exception\DuplicateVersionException;
use Webware\Migration\MigrationInterface;

use function array_key_exists;
use function array_values;
use function usort;

/**
 * Orders migrations deterministically by ascending version and rejects
 * ambiguous sets (FR-002, FR-009).
 */
final readonly class MigrationDiscovery
{
    /**
     * @param list<MigrationInterface> $migrations
     */
    public function __construct(
        private array $migrations,
    ) {}

    /**
     * @return list<MigrationInterface>
     * @throws DuplicateVersionException
     */
    public function discover(): array
    {
        $migrations = $this->migrations;
        usort(
            array   : $migrations,
            callback: static fn(
                MigrationInterface $a,
                MigrationInterface $b,
            ): int => $a->getVersion() <=> $b->getVersion(),
        );

        $seen = [];
        foreach ($migrations as $migration) {
            $version = $migration->getVersion();

            if (array_key_exists(
                key  : $version,
                array: $seen,
            )) {
                throw DuplicateVersionException::forVersion($version);
            }

            $seen[$version] = true;
        }

        return array_values(array: $migrations);
    }
}
