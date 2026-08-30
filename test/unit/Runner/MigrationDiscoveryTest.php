<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Runner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Migration\Exception\DuplicateVersionException;
use Webware\Migration\MigrationInterface;
use Webware\Migration\Runner\MigrationDiscovery;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;

#[CoversClass(MigrationDiscovery::class)]
#[CoversMethod(MigrationDiscovery::class, '__construct')]
#[CoversMethod(MigrationDiscovery::class, 'discover')]
final class MigrationDiscoveryTest extends TestCase
{
    #[Test]
    public function rejectsDuplicateVersions(): void
    {
        $first  = $this->createStub(MigrationInterface::class);
        $second = $this->createStub(MigrationInterface::class);
        $first->method('getVersion')->willReturn(5);
        $second->method('getVersion')->willReturn(5);

        $this->expectException(exception: DuplicateVersionException::class);

        new MigrationDiscovery(migrations: [$first, $second])->discover();
    }

    #[Test]
    public function returnsEmptyListWhenNoMigrations(): void
    {
        static::assertSame(
            expected: [],
            actual  : new MigrationDiscovery(migrations: [])->discover(),
        );
    }

    #[Test]
    public function sortsMigrationsAscendingByVersion(): void
    {
        $discovery = new MigrationDiscovery(migrations: [
            new Migration002AddRoleColumn(),
            new Migration001CreateRoles(),
        ]);

        $result = $discovery->discover();

        static::assertSame(
            expected: 1,
            actual  : $result[0]->getVersion(),
        );
        static::assertSame(
            expected: 2,
            actual  : $result[1]->getVersion(),
        );
    }
}
