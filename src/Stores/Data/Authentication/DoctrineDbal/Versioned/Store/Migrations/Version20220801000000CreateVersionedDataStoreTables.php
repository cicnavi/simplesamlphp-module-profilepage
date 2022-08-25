<?php

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;

class Version20220801000000CreateVersionedDataStoreTables extends AbstractMigration
{
    /**
     * @inheritDoc
     * @throws MigrationException
     */
    public function run(): void
    {
//        $tableNameIdpVersion = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP_VERSION);
//
//        $tableNameSp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_SP);
//        $tableNameSpVersion = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_SP_VERSION);
//
//        $tableNameUser = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_USER);
//        $tableUserAttributeVersion =
// $this->preparePrefixedTableName(TableConstants::TABLE_NAME_USER_ATTRIBUTE_VERSION);
//
//        $tableNameSpUserAttributeVersion = $this->preparePrefixedTableName(
//            TableConstants::TABLE_NAME_SP_USER_ATTRIBUTE_VERSION
//        );
//
//        $tableNameAuthentication = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_AUTHENTICATION);
//
//        $tableNameIdpSpUserAttributeSetHistory = $this->preparePrefixedTableName(
//            TableConstants::TABLE_NAME_IDP_SP_USER_ATTRIBUTE_SET_HISTORY
//        );
//
        $tablesToCreate = [
            $this->prepareTableIdp(),
            $this->prepareTableIdpVersion(),
        ];

        foreach ($tablesToCreate as $table) {
            try {
                $this->schemaManager->createTable($table);
            } catch (\Throwable $exception) {
                throw $this->prepareGenericMigrationException(
                    \sprintf('Erorr creating table \'%s', $table->getName()),
                    $exception
                );
            }
        }
    }

    /**
     * @inheritDoc
     * @throws MigrationException
     */
    public function revert(): void
    {
        $tablesToDrop = [
            $this->preparePrefixedTableName('idp'),
        ];

        foreach ($tablesToDrop as $table) {
            try {
                $this->schemaManager->dropTable($table);
            } catch (\Throwable $exception) {
                throw $this->prepareGenericMigrationException(\sprintf('Could not drop table %s.', $table), $exception);
            }
        }
    }

    protected function getLocalTablePrefix(): string
    {
        return 'vds_';
    }

    /**
     * @throws MigrationException
     */
    protected function prepareTableIdp(): Table
    {
        $tableName = $this->preparePrefixedTableName('idp');

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('entity_id', Types::STRING)
                ->setLength(TableConstants::TABLE_IDP_COLUMN_ENTITY_ID_LENGTH);

            $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey(['id']);

            $table->addUniqueConstraint(['entity_id']);

            return $table;
        } catch (\Throwable $exception) {
            throw $this->prepareGenericMigrationException(
                \sprintf('Error preparing Table instance for \'%s\'.', $tableName),
                $exception
            );
        }
    }

    protected function prepareTableIdpVersion(): Table
    {
        $tableName = $this->preparePrefixedTableName('idp_version');

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('idp_id', Types::BIGINT)
                ->setUnsigned(true);


//            $table->addColumn('entity_id', Types::STRING)
//                ->setLength(TableConstants::TABLE_IDP_COLUMN_ENTITY_ID_LENGTH);
//
//            $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);
//
//            $table->setPrimaryKey(['id']);
//
//            $table->add(['entity_id']);

            return $table;
        } catch (\Throwable $exception) {
            throw $this->prepareGenericMigrationException(
                \sprintf('Error preparing Table instance for \'%s\'.', $tableName),
                $exception
            );
        }
    }
}
