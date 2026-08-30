<?php

declare(strict_types=1);

namespace Webware\Migration\Repository;

use Closure;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Exception\ExceptionInterface as PhpDbException;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Timestamp;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Sql;
use Throwable;

use function is_array;

/**
 * php-db persistence for the `schema_migrations` tracking table (R-002, R-004).
 *
 * The only php-db-touching layer; returns natural arrays, never php-db types.
 *
 * @internal
 */
final class PhpDbMigrationRepository implements MigrationRepositoryInterface
{
    public const string TABLE = 'schema_migrations';

    private readonly Sql $sql;

    /**
     * @throws PhpDbException
     */
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {
        $this->sql = new Sql(adapter: $adapter);
        $this->createTableIfNotExists();
    }

    /**
     * @throws PhpDbException
     */
    #[Override]
    public function fetchApplied(): array
    {
        $select = $this->sql->select(table: self::TABLE)
            ->columns(columns: ['version', 'description', 'applied_at', 'checksum'])
            ->order(order: 'version ASC');

        $rows = [];
        foreach ($this->execute($select) as $row) {
            if (! is_array(value: $row)) {
                continue;
            }

            $rows[] = $this->hydrateRow($row);
        }

        return $rows;
    }

    /**
     * @throws PhpDbException
     */
    #[Override]
    public function findApplied(int $version): ?array
    {
        $select = $this->sql->select(table: self::TABLE)
            ->columns(columns: ['version', 'description', 'applied_at', 'checksum'])
            ->where(predicate: ['version' => $version])
            ->limit(limit: 1);

        foreach ($this->execute($select) as $row) {
            if (! is_array(value: $row)) {
                continue;
            }

            return $this->hydrateRow($row);
        }

        return null;
    }

    /**
     * @throws PhpDbException
     */
    #[Override]
    public function recordApplied(int $version, string $description, string $checksum): void
    {
        $insert = $this->sql->insert(table: self::TABLE)
            ->values(values: [
                'version'     => $version,
                'description' => $description,
                'applied_at'  => new Literal(literal: 'CURRENT_TIMESTAMP'),
                'checksum'    => $checksum,
            ]);

        $this->execute($insert);
    }

    /**
     * @throws PhpDbException
     */
    #[Override]
    public function removeApplied(int $version): void
    {
        $delete = $this->sql->delete(table: self::TABLE)->where(predicate: ['version' => $version]);

        $this->execute($delete);
    }

    /**
     * @throws Throwable
     */
    #[Override]
    public function transactional(Closure $operation): void
    {
        $connection = $this->adapter->getDriver()->getConnection();

        $connection->beginTransaction();

        try {
            $operation();
            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollback();

            throw $e;
        }
    }

    /**
     * @throws PhpDbException
     */
    private function createTableIfNotExists(): void
    {
        $ddl = new CreateTable(table: self::TABLE);
        $ddl->ifNotExists();
        $ddl->addColumn(column: new Integer(
            name    : 'version',
            nullable: false,
        ));
        $ddl->addColumn(column: new Varchar(
            name    : 'description',
            length  : 255,
            nullable: false,
        ));
        $ddl->addColumn(column: new Timestamp(
            name    : 'applied_at',
            nullable: false,
        ));
        $ddl->addColumn(column: new Varchar(
            name    : 'checksum',
            length  : 64,
            nullable: false,
        ));
        $ddl->addConstraint(constraint: new PrimaryKey(columns: ['version']));

        $this->adapter->getDriver()
            ->getConnection()
            ->execute(
                sql: $this->sql->buildSqlString(sqlObject: $ddl),
            );
    }

    /**
     * @throws PhpDbException
     */
    private function execute(PreparableSqlInterface $sqlObject): ResultInterface
    {
        return $this->adapter->executeQuery(
            sql: $this->sql->prepareStatementForSqlObject(sqlObject: $sqlObject),
        );
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array{version: int, description: string, applied_at: string, checksum: string}
     */
    private function hydrateRow(array $row): array
    {
        return [
            'version'     => (int) ($row['version'] ?? 0),
            'description' => (string) ($row['description'] ?? ''),
            'applied_at'  => (string) ($row['applied_at'] ?? ''),
            'checksum'    => (string) ($row['checksum'] ?? ''),
        ];
    }
}
