<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

class Version20240505400CreateConnectedServiceTable extends AbstractMigration
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
        $tableName = $this->preparePrefixedTableName('connected_service');

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('sp_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('user_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('user_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('first_authentication_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('last_authentication_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('count', Types::BIGINT)->setUnsigned(true);

            $table->addColumn('created_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('updated_at', Types::BIGINT)
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
                $this->preparePrefixedTableName('user', $versionedDataStoreTablePrefix),
                ['user_id'],
                ['id']
            );

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('user_version', $versionedDataStoreTablePrefix),
                ['user_version_id'],
                ['id']
            );

            // Old data can be deleted using last_authentication_at column, so add index for it.
            $table->addIndex(['last_authentication_at']);

            $table->addUniqueConstraint(['sp_id', 'user_id']);

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
        $tableName = $this->preparePrefixedTableName('connected_service');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}
