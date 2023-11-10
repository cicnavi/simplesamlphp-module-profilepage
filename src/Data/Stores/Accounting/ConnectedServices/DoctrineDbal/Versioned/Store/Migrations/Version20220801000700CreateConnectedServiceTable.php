<?php

declare(strict_types=1);

//phpcs:ignore
namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

class Version20220801000700CreateConnectedServiceTable extends AbstractMigration
{
    protected function getLocalTablePrefix(): string
    {
        return 'vds_';
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

            $table->addColumn('idp_sp_user_version_id', Types::BIGINT)
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
                $this->preparePrefixedTableName('idp_sp_user_version'),
                ['idp_sp_user_version_id'],
                ['id']
            );

            // Old data can be deleted using updated_at column, so add index for it.
            $table->addIndex(['updated_at']);

            $table->addUniqueConstraint(['idp_sp_user_version_id']);

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
