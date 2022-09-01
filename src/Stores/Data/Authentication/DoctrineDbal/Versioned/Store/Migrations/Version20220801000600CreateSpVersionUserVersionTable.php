<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;

class Version20220801000600CreateSpVersionUserVersionTable extends AbstractMigration
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
        $tableName = $this->preparePrefixedTableName('sp_version_user_version');

        try {
            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('sp_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('user_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey(['id']);

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('sp_version'),
                ['sp_version_id'],
                ['id']
            );

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('user_version'),
                ['user_version_id'],
                ['id']
            );

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
        $tableName = $this->preparePrefixedTableName('sp_version_user_version');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (\Throwable $exception) {
            throw $this->prepareGenericMigrationException(\sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}