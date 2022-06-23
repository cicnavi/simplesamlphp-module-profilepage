<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class Migrator
{
    public const TABLE_NAME = 'migrations';

    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_SCOPE = 'scope';
    public const COLUMN_NAME_VERSION = 'version';

    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;
    protected string $prefixedTableName;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->prefixedTableName = $this->connection->preparePrefixedTableName(self::TABLE_NAME);
    }

    public function needsSetUp(): bool
    {
        return ! $this->schemaManager->tablesExist($this->prefixedTableName);
    }

    public function runSetUp(): void
    {
        if (! $this->needsSetUp()) {
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
