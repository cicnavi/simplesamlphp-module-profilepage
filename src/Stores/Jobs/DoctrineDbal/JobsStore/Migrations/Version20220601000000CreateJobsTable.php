<?php

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;

class Version20220601000000CreateJobsTable extends AbstractMigration
{
    public function run(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_JOBS);
        $table = new Table($tableName);

        $table->addColumn('id', Types::BIGINT)
            ->setUnsigned(true)
            ->setAutoincrement(true);

        $table->addColumn('payload', Types::TEXT);
        $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);
        $table->addColumn('reserved_at', Types::DATETIMETZ_IMMUTABLE)->setNotnull(false);
        $table->addColumn('attempts', Types::INTEGER)->setNotnull(false);

        $table->setPrimaryKey(['id']);

        $this->schemaManager->createTable($table);
    }

    public function revert(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_JOBS);

        $this->schemaManager->dropTable($tableName);
    }
}
