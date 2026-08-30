<?php

declare(strict_types=1);

namespace WebwareTest\Migration\ReadModel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Migration\ReadModel\AppliedMigration;
use Webware\Migration\ReadModel\MigrationInfo;

#[CoversClass(AppliedMigration::class)]
#[CoversClass(MigrationInfo::class)]
#[CoversMethod(AppliedMigration::class, '__construct')]
#[CoversMethod(MigrationInfo::class, '__construct')]
final class ReadModelTest extends TestCase
{
    #[Test]
    public function appliedMigrationHoldsValues(): void
    {
        $model = new AppliedMigration(
            version    : 1,
            description: 'Create Roles',
            appliedAt  : '2026-01-01 00:00:00',
            checksum   : 'abc123',
        );

        static::assertSame(
            expected: 1,
            actual  : $model->version,
        );
        static::assertSame(
            expected: 'Create Roles',
            actual  : $model->description,
        );
        static::assertSame(
            expected: '2026-01-01 00:00:00',
            actual  : $model->appliedAt,
        );
        static::assertSame(
            expected: 'abc123',
            actual  : $model->checksum,
        );
    }

    #[Test]
    public function migrationInfoHoldsValues(): void
    {
        $model = new MigrationInfo(
            version    : 2,
            description: 'Add Role Column',
            status     : MigrationInfo::STATUS_PENDING,
        );

        static::assertSame(
            expected: 2,
            actual  : $model->version,
        );
        static::assertSame(
            expected: 'Add Role Column',
            actual  : $model->description,
        );
        static::assertSame(
            expected: 'pending',
            actual  : $model->status,
        );
        static::assertSame(
            expected: 'applied',
            actual  : MigrationInfo::STATUS_APPLIED,
        );
        static::assertSame(
            expected: 'pending',
            actual  : MigrationInfo::STATUS_PENDING,
        );
    }
}
