<?php

declare(strict_types=1);

namespace Webware\Migration\Exception;

use RuntimeException;

use function sprintf;

/**
 * Thrown when an already-applied migration's source has changed since it was
 * applied (FR-011).
 */
final class ChecksumMismatchException extends RuntimeException implements ExceptionInterface
{
    public static function forVersion(int $version): self
    {
        return new self(sprintf(
            'Checksum mismatch for applied migration version %d; its source has changed since it was applied.',
            $version,
        ));
    }
}
