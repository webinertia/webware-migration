<?php

declare(strict_types=1);

namespace WebwareTest\Migration\Fixture;

use Override;
use RuntimeException;
use Webware\Migration\Migration\AbstractMigration;

/**
 * @internal
 */
final class Migration999FailOnApply extends AbstractMigration
{
    /** @var list<string> */
    public array $events = [];

    #[Override]
    public function down(): void
    {
        $this->events[] = 'down';
    }

    #[Override]
    public function up(): void
    {
        $this->events[] = 'up';

        throw new RuntimeException('apply failed');
    }
}
