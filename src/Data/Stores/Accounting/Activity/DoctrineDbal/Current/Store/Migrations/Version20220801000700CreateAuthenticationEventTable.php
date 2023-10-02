<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\TableConstants as BaseTableConstantsAlias;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

class Version20220801000700CreateAuthenticationEventTable extends AbstractMigration
{
    protected function getLocalTablePrefix(): string
    {
        return 'cds_';
    }

    /**
     * @inheritDoc
     * @throws MigrationException
     */
    public function run(): void
    {
        $tableName = $this->preparePrefixedTableName('authentication_event');

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('sp_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('user_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('happened_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('client_ip_address', Types::STRING)
                ->setLength(BaseTableConstantsAlias::COLUMN_IP_ADDRESS_LENGTH)
                ->setNotnull(false);

            $table->addColumn('authentication_protocol_designation', Types::STRING)
                ->setLength(BaseTableConstantsAlias::COLUMN_AUTHENTICATION_PROTOCOL_DESIGNATION_LENGTH)
                ->setNotnull(false);

            $table->addColumn('created_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->setPrimaryKey(['id']);

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('sp'),
                ['sp_id'],
                ['id']
            );

            // We are using versioned data for user management.
            $versionedDataStoreTablePrefix = 'vds_';
            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('user_version', $versionedDataStoreTablePrefix),
                ['user_version_id'],
                ['id']
            );

            // Old data can be deleted using happened_at column, so add index for it.
            $table->addIndex(['happened_at']);

            $this->schemaManager->createTable($table);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(
                sprintf('Error creating table \'%s.', $tableName),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     * @throws MigrationException
     */
    public function revert(): void
    {
        $tableName = $this->preparePrefixedTableName('authentication_event');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}
