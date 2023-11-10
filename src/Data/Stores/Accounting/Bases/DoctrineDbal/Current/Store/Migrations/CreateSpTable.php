<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\TableConstants as BaseTableConstantsAlias;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use Throwable;

use function sprintf;

class CreateSpTable extends AbstractMigration
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
        $tableName = $this->preparePrefixedTableName('sp');

        /** @noinspection DuplicatedCode */
        try {
            if ($this->schemaManager->tablesExist($tableName)) {
                return;
            }

            $table = new Table($tableName);

            $table->addColumn('id', Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn('entity_id', Types::STRING)
                ->setLength(BaseTableConstantsAlias::COLUMN_ENTITY_ID_LENGTH);

            $table->addColumn('entity_id_hash_sha256', Types::STRING)
                ->setLength(BaseTableConstantsAlias::COLUMN_HASH_SHA265_HEXITS_LENGTH)
                ->setFixed(true);

            $table->addColumn('metadata', Types::TEXT);

            $table->addColumn('metadata_hash_sha256', Types::STRING)
                ->setLength(BaseTableConstantsAlias::COLUMN_HASH_SHA265_HEXITS_LENGTH)
                ->setFixed(true);

            $table->addColumn('created_at', Types::BIGINT)
                ->setUnsigned(true);
            $table->addColumn('updated_at', Types::BIGINT)
                ->setUnsigned(true);

            $table->setPrimaryKey(['id']);

            $table->addUniqueConstraint(['entity_id_hash_sha256']);
            $table->addIndex(['metadata_hash_sha256']);

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
        $tableName = $this->preparePrefixedTableName('sp');

        try {
            $this->schemaManager->dropTable($tableName);
        } catch (Throwable $exception) {
            throw $this->prepareGenericMigrationException(sprintf('Could not drop table %s.', $tableName), $exception);
        }
    }
}
