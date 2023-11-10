<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

abstract class AbstractCreateJobsTable extends AbstractMigration
{
    /**
     * @throws MigrationException
     */
    public function run(): void
    {
        $tableName = $this->preparePrefixedTableName($this->getJobsTableName());

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('type', Types::STRING)
                ->setLength(TableConstants::COLUMN_TYPE_LENGTH);

            $table->addColumn('payload', Types::TEXT);
            $table->addColumn('created_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->setPrimaryKey(['id']);

            $this->schemaManager->createTable($table);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(
                sprintf('Could not create table %s.', $tableName),
                $exception
            );
        }
    }

    /**
     * @throws MigrationException
     */
    public function revert(): void
    {
        $tableName = $this->preparePrefixedTableName($this->getJobsTableName());

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }

    abstract protected function getJobsTableName(): string;
}
