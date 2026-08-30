<?php

declare(strict_types=1);

namespace Webware\Migration\Exception;

use RuntimeException;

use function sprintf;

/**
 * Thrown when two migrations declare the same version (FR-009).
 */
final class DuplicateVersionException extends RuntimeException implements ExceptionInterface
{
    public static function forVersion(int $version): self
    {
        return new self(sprintf('Duplicate migration version: %d.', $version));
    }
}
