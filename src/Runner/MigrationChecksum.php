<?php

declare(strict_types=1);

namespace Webware\Migration\Runner;

use ReflectionClass;
use ReflectionException;
use Webware\Migration\MigrationInterface;

use function file_get_contents;
use function hash;

/**
 * Computes the SHA-256 integrity checksum of a migration's source file (R-007).
 */
final readonly class MigrationChecksum
{
    /**
     * @throws ReflectionException
     */
    public function compute(MigrationInterface $migration): string
    {
        /** @var string $file */
        $file = new ReflectionClass(objectOrClass: $migration)->getFileName();

        /** @var string $contents */
        $contents = file_get_contents(filename: $file);

        return hash(
            algo: 'sha256',
            data: $contents,
        );
    }
}
