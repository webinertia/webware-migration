<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Runner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Webware\Migration\Runner\MigrationChecksum;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;

use function file_get_contents;
use function hash;

#[CoversClass(MigrationChecksum::class)]
#[CoversMethod(MigrationChecksum::class, 'compute')]
final class MigrationChecksumTest extends TestCase
{
    #[Test]
    public function computesSha256OfMigrationSourceFile(): void
    {
        $migration = new Migration001CreateRoles();
        $file      = (string) new ReflectionClass(objectOrClass: $migration)->getFileName();

        $expected = hash(
            algo: 'sha256',
            data: (string) file_get_contents(filename: $file),
        );

        static::assertSame(
            expected: $expected,
            actual  : new MigrationChecksum()->compute($migration),
        );
    }
}
