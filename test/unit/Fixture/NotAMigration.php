<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Fixture;

use Override;
use Webware\Migration\Migration\AbstractMigration;

/**
 * @internal
 */
final class NotAMigration extends AbstractMigration
{
    #[Override]
    public function down(): void {}

    #[Override]
    public function up(): void {}
}
