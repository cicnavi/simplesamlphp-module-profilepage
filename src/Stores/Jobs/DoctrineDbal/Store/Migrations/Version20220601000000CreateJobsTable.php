<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use Throwable;

class Version20220601000000CreateJobsTable extends AbstractMigration
{
    /**
     * @throws MigrationException
     */
    public function run(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(Store::TABLE_NAME_JOBS);

        try {
            $table = new Table($tableName);

            $table->addColumn(Store::COLUMN_NAME_ID, Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn(Store::COLUMN_NAME_TYPE, Types::STRING)
                ->setLength(Store::COLUMN_LENGTH_TYPE);

            $table->addColumn(Store::COLUMN_NAME_PAYLOAD, Types::TEXT);
            $table->addColumn(Store::COLUMN_NAME_CREATED_AT, Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey([Store::COLUMN_NAME_ID]);

            $this->schemaManager->createTable($table);
        } catch (Throwable $exception) {
            $contextDetails = sprintf('Could not create table %s.', $tableName);
            $this->throwGenericMigrationException($contextDetails, $exception);
        }
    }

    /**
     * @throws MigrationException
     */
    public function revert(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(Store::TABLE_NAME_JOBS);

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            $contextDetails = sprintf('Could not drop table %s.', $tableName);
            $this->throwGenericMigrationException($contextDetails, $exception);
        }
    }
}
