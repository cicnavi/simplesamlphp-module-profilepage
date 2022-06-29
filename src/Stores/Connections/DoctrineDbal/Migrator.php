<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;

class Migrator extends AbstractMigrator
{
    public const TABLE_NAME = 'migrations';

    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_SCOPE = 'scope';
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
        $table->addColumn(self::COLUMN_NAME_SCOPE, Types::STRING);
        $table->addColumn(self::COLUMN_NAME_VERSION, Types::STRING);
        $table->addColumn(self::COLUMN_NAME_CREATED_AT, Types::DATETIMETZ_IMMUTABLE);

        $table->setPrimaryKey(['id']);

        $this->schemaManager->createTable($table);
    }

    /**
     * @param array<MigrationInterface> $migrations
     * @return void
     */
    public function migrate(array $migrations): void
    {
    }
}
