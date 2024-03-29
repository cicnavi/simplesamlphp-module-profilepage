<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\TableConstants as BaseTableConstantsAlias;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

class CreateUserTable extends AbstractMigration
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
        $tableName = $this->preparePrefixedTableName('user');

        try {
            if ($this->schemaManager->tablesExist($tableName)) {
                return;
            }

            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('identifier', Types::TEXT)
                ->setLength(65535);

            $table->addColumn('identifier_hash_sha256', Types::STRING)
                ->setLength(BaseTableConstantsAlias::COLUMN_HASH_SHA265_HEXITS_LENGTH)
                ->setFixed(true);

            $table->addColumn('created_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->setPrimaryKey(['id']);

            $table->addUniqueConstraint(['identifier_hash_sha256']);

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
        $tableName = $this->preparePrefixedTableName('user');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}
