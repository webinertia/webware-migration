<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Fixture;

use Override;
use Webware\Migration\Migration\AbstractMigration;

/**
 * Schema migration — version `000` creates the component's schema.
 *
 * @internal
 */
final class Migration000CreateSchema extends AbstractMigration
{
    #[Override]
    public function down(): void {}

    #[Override]
    public function up(): void {}
}
