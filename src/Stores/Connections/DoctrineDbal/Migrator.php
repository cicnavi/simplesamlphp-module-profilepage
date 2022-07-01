<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\InvalidValueException;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;

class Migrator extends AbstractMigrator
{
    public const TABLE_NAME = 'migrations';

    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_VERSION = 'version';
    public const COLUMN_NAME_CREATED_AT = 'created_at';

    protected Connection $connection;
    protected LoggerService $loggerService;

    protected AbstractSchemaManager $schemaManager;
    protected string $prefixedTableName;

    public function __construct(Connection $connection, LoggerService $loggerService)
    {
        $this->connection = $connection;
        $this->loggerService = $loggerService;

        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->prefixedTableName = $this->connection->preparePrefixedTableName(self::TABLE_NAME);
    }

    public function needsSetup(): bool
    {
        return ! $this->schemaManager->tablesExist([$this->prefixedTableName]);
    }

    public function runSetup(): void
    {
        if (! $this->needsSetup()) {
            $this->loggerService->warning('Migrator setup has been called, however setup is not needed.');
            return;
        }

        $this->createMigrationsTable();
    }

    protected function createMigrationsTable(): void
    {
        $table = new Table($this->prefixedTableName);

        $table->addColumn(self::COLUMN_NAME_ID, Types::BIGINT)
            ->setAutoincrement(true)
            ->setUnsigned(true);
        $table->addColumn(self::COLUMN_NAME_VERSION, Types::STRING);
        $table->addColumn(self::COLUMN_NAME_CREATED_AT, Types::DATETIMETZ_IMMUTABLE);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex([self::COLUMN_NAME_VERSION]);

        $this->schemaManager->createTable($table);
    }

    protected function buildMigrationClassInstance(string $migrationClass): MigrationInterface
    {
        $this->validateDoctrineDbalMigrationClass($migrationClass);

        /** @var MigrationInterface $migration */
        $migration = (new \ReflectionClass($migrationClass))->newInstance($this->connection);

        return $migration;
    }

    protected function validateDoctrineDbalMigrationClass(string $migrationClass): void
    {
        if (! is_subclass_of($migrationClass, AbstractMigration::class)) {
            throw new InvalidValueException('Migration class is not Doctrine DBAL migration.');
        }
    }

    protected function markImplementedMigrationClass(string $migrationClass): void
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $queryBuilder->insert($this->prefixedTableName)
            ->values(
                [
                    self::COLUMN_NAME_VERSION => ':' . self::COLUMN_NAME_VERSION,
                    self::COLUMN_NAME_CREATED_AT => ':' . self::COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    self::COLUMN_NAME_VERSION => $migrationClass,
                    self::COLUMN_NAME_CREATED_AT => new \DateTimeImmutable(),
                ],
                [
                    self::COLUMN_NAME_VERSION => Types::STRING,
                    self::COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
                ]
            );

        $queryBuilder->executeStatement();
    }

    public function getImplementedMigrationClasses(): array
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $queryBuilder->select(self::COLUMN_NAME_VERSION)
            ->from($this->prefixedTableName);

        /** @var class-string[] $migrationClasses */
        $migrationClasses = $queryBuilder->executeQuery()->fetchFirstColumn();

        return $migrationClasses;
    }
}
