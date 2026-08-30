<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Migration;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Migration\Migration\AbstractMigration;
use WebwareTest\Migration\Fixture\Migration000ZeroVersion;
use WebwareTest\Migration\Fixture\Migration001CreateRoles;
use WebwareTest\Migration\Fixture\Migration002AddRoleColumn;
use WebwareTest\Migration\Fixture\NotAMigration;
use WebwareTest\Migration\Fixture\NotMigration001Foo;

#[CoversClass(AbstractMigration::class)]
#[CoversMethod(AbstractMigration::class, '__construct')]
#[CoversMethod(AbstractMigration::class, 'getDescription')]
#[CoversMethod(AbstractMigration::class, 'getVersion')]
final class AbstractMigrationTest extends TestCase
{
    #[Test]
    public function allowsExplicitVersionAndDescription(): void
    {
        $migration = new Migration001CreateRoles(
            version    : 42,
            description: 'Custom description',
        );

        static::assertSame(
            expected: 42,
            actual  : $migration->getVersion(),
        );
        static::assertSame(
            expected: 'Custom description',
            actual  : $migration->getDescription(),
        );
    }

    #[Test]
    public function extractsDescriptionFromClassName(): void
    {
        static::assertSame(
            expected: 'Create Roles',
            actual  : new Migration001CreateRoles()->getDescription(),
        );
        static::assertSame(
            expected: 'Add Role Column',
            actual  : new Migration002AddRoleColumn()->getDescription(),
        );
    }

    #[Test]
    public function extractsVersionFromClassName(): void
    {
        static::assertSame(
            expected: 1,
            actual  : new Migration001CreateRoles()->getVersion(),
        );
        static::assertSame(
            expected: 2,
            actual  : new Migration002AddRoleColumn()->getVersion(),
        );
    }

    #[Test]
    public function rejectsClassNameWithoutVersionPrefix(): void
    {
        $this->expectException(exception: InvalidArgumentException::class);
        $this->expectExceptionMessage('must be named Migration');

        new NotAMigration();
    }

    #[Test]
    public function rejectsVersionPrefixNotAtStartOfName(): void
    {
        $this->expectException(exception: InvalidArgumentException::class);
        $this->expectExceptionMessage('must be named Migration');

        new NotMigration001Foo();
    }

    #[Test]
    public function rejectsZeroVersion(): void
    {
        $this->expectException(exception: InvalidArgumentException::class);
        $this->expectExceptionMessage('positive integer');

        new Migration000ZeroVersion();
    }
}
