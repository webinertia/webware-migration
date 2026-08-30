<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Migration\Exception\ChecksumMismatchException;
use Webware\Migration\Exception\DuplicateVersionException;
use Webware\Migration\Exception\ExceptionInterface;

#[CoversClass(DuplicateVersionException::class)]
#[CoversClass(ChecksumMismatchException::class)]
#[CoversMethod(DuplicateVersionException::class, 'forVersion')]
#[CoversMethod(ChecksumMismatchException::class, 'forVersion')]
final class ExceptionTest extends TestCase
{
    #[Test]
    public function checksumMismatchExceptionFormatsMessage(): void
    {
        $exception = ChecksumMismatchException::forVersion(3);

        static::assertStringContainsString(
            needle  : 'version 3',
            haystack: $exception->getMessage(),
        );
        static::assertInstanceOf(
            expected: ExceptionInterface::class,
            actual  : $exception,
        );
    }

    #[Test]
    public function duplicateVersionExceptionFormatsMessage(): void
    {
        $exception = DuplicateVersionException::forVersion(5);

        static::assertSame(
            expected: 'Duplicate migration version: 5.',
            actual  : $exception->getMessage(),
        );
        static::assertInstanceOf(
            expected: ExceptionInterface::class,
            actual  : $exception,
        );
    }
}
