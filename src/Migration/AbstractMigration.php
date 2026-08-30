<?php

declare(strict_types=1);

namespace Webware\Migration\Migration;

use InvalidArgumentException;
use Override;
use ReflectionClass;
use ReflectionException;
use Webware\Migration\MigrationInterface;

use function preg_match;
use function preg_replace;
use function sprintf;
use function trim;

/**
 * Optional base implementation deriving version and description from the class
 * short name, which MUST follow the `Migration{NNN}{PascalDescription}` form.
 *
 * @api
 */
abstract class AbstractMigration implements MigrationInterface
{
    private readonly int $version;

    private readonly string $description;

    /**
     * @throws InvalidArgumentException when the class name does not encode a positive version
     * @throws ReflectionException
     */
    public function __construct(?int $version = null, ?string $description = null)
    {
        $shortName = new ReflectionClass(objectOrClass: $this)->getShortName();

        $this->version     = $version ?? self::extractVersion($shortName);
        $this->description = $description ?? self::extractDescription($shortName);
    }

    private static function extractDescription(string $shortName): string
    {
        /** @var string $suffix */
        $suffix = preg_replace(
            pattern    : '/^Migration\d+/',
            replacement: '',
            subject    : $shortName,
        );

        /** @var string $spaced */
        $spaced = preg_replace(
            pattern    : '/(?<=\p{Ll})(?=\p{Lu})/',
            replacement: ' ',
            subject    : $suffix,
        );

        return trim(string: $spaced);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function extractVersion(string $shortName): int
    {
        $matches = [];
        $matched = preg_match(
            pattern: '/^Migration(\d+)/',
            subject: $shortName,
            matches: $matches,
        );

        if (1 !== $matched) {
            throw new InvalidArgumentException(message: sprintf(
                'Migration class "%s" must be named Migration{NNN}{Description}.',
                $shortName,
            ));
        }

        $version = (int) ($matches[1] ?? '0');

        if ($version <= 0) {
            throw new InvalidArgumentException(message: sprintf(
                'Migration version in "%s" must be a positive integer.',
                $shortName,
            ));
        }

        return $version;
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->description;
    }

    #[Override]
    public function getVersion(): int
    {
        return $this->version;
    }
}
