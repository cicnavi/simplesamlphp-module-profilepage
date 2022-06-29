<?php

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;

class Version20220601000000CreateJobsTable extends AbstractMigration
{
    public function up(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME);
        $table = new Table($tableName);

        $table->addColumn('id', Types::BIGINT)
            ->setUnsigned(true)
            ->setAutoincrement(true);

        $table->addColumn('payload', Types::TEXT);
        $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);
        $table->addColumn('reserved_at', Types::DATETIMETZ_IMMUTABLE)->setNotnull(false);
        $table->addColumn('attempts', Types::INTEGER)->setNotnull(false);
    }

    public function down(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME);

        $this->schemaManager->dropTable($tableName);
    }
}
