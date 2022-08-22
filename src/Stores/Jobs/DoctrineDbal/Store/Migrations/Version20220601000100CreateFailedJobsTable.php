<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use Throwable;

class Version20220601000100CreateFailedJobsTable extends AbstractMigration
{
    /**
     * @throws MigrationException
     */
    public function run(): void
    {
        $tableName = $this->connection->preparePrefixedTableName(Store::TABLE_NAME_FAILED_JOBS);
        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('payload', Types::TEXT);
            $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey(['id']);

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
        $tableName = $this->connection->preparePrefixedTableName(Store::TABLE_NAME_FAILED_JOBS);

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            $contextDetails = sprintf('Could not drop table %s.', $tableName);
            $this->throwGenericMigrationException($contextDetails, $exception);
        }
    }
}
