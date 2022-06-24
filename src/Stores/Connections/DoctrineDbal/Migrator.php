<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Services\LoggerService;

class Migrator
{
    public const TABLE_NAME = 'migrations';

    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_SCOPE = 'scope';
    public const COLUMN_NAME_VERSION = 'version';

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
        $migrationsTable = new Table($this->prefixedTableName);

        $idColumn = $migrationsTable->addColumn(self::COLUMN_NAME_ID, Types::BIGINT);
        $idColumn->setAutoincrement(true);
        $idColumn->setUnsigned(true);

        $migrationsTable->addColumn(self::COLUMN_NAME_SCOPE, Types::STRING);

        $migrationsTable->addColumn(self::COLUMN_NAME_VERSION, Types::STRING);

        $migrationsTable->setPrimaryKey(['id']);

        $this->schemaManager->createTable($migrationsTable);
    }
}
