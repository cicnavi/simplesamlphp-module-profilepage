<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

class CreateIdpSpUserVersionTable extends AbstractMigration
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
        $tableName = $this->preparePrefixedTableName('idp_sp_user_version');

        try {
            if ($this->schemaManager->tablesExist($tableName)) {
                return;
            }

            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('idp_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('sp_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('user_version_id', Types::BIGINT)
                ->setUnsigned(true);

            $table->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey(['id']);

            $table->addForeignKeyConstraint(
                $this->preparePrefixedTableName('idp_version'),
                ['idp_version_id'],
                ['id']
            );

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

            $table->addUniqueConstraint(['idp_version_id', 'sp_version_id', 'user_version_id']);

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
        $tableName = $this->preparePrefixedTableName('idp_sp_user_version');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}
