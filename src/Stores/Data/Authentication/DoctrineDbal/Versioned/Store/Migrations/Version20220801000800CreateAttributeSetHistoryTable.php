<?php

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;

class Version20220801000800CreateAttributeSetHistoryTable extends AbstractMigration
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
        $tableName = $this->preparePrefixedTableName('attribute_set_history');

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('idp_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('sp_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('user_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('attributes', Types::TEXT)
                ->setComment('Serialized attributes.');

            $table->addColumn('updated_by_attributes_hash_sha_256', Types::STRING)
                ->setLength(TableConstants::COLUMN_HASH_SHA265_HEXITS_LENGTH)
                ->setFixed(true);

            $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);

            $table->addColumn('updated_at', Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey(['id']);

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('idp'),
                ['idp_id'],
                ['id']
            );

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('sp'),
                ['sp_id'],
                ['id']
            );

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('user'),
                ['user_id'],
                ['id']
            );

            $table->addUniqueConstraint(['idp_id', 'sp_id', 'user_id']);

            $this->schemaManager->createTable($table);
        } catch (\Throwable $exception) {
            throw $this->prepareGenericMigrationException(
                \sprintf('Error creating table \'%s.', $tableName),
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
        $tableName = $this->preparePrefixedTableName('attribute_set_history');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (\Throwable $exception) {
            throw $this->prepareGenericMigrationException(\sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}
