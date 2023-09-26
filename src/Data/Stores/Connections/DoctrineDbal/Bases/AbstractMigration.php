<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use Throwable;

abstract class AbstractMigration implements MigrationInterface
{
    protected AbstractSchemaManager $schemaManager;

    /**
     * @throws StoreException
     */
    public function __construct(protected Connection $connection)
    {
        try {
            $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        } catch (Throwable $exception) {
            $message = 'Could not create DBAL schema manager.';
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
    }

    protected function prepareGenericMigrationException(
        string $contextDetails,
        Throwable $throwable
    ): MigrationException {
        $message = sprintf(
            'There was an error running a migration class %s. Context details: %s. Error was: %s.',
            static::class,
            $contextDetails,
            $throwable->getMessage()
        );

        /** @noinspection PhpCastIsUnnecessaryInspection */
        return new MigrationException($message, (int)$throwable->getCode(), $throwable);
    }

    /**
     * Prepare prefixed table name which will include table prefix from connection, local table prefix, and table name.
     */
    protected function preparePrefixedTableName(string $tableName, string $tablePrefixOverride = null): string
    {
        $tablePrefix = $tablePrefixOverride ?? $this->getLocalTablePrefix();

        return $this->connection->preparePrefixedTableName($tablePrefix . $tableName);
    }

    /**
     * Get local table prefix (prefix per migration). Empty string by default. Override in particular migration to
     * set another local prefix.
     *
     * @return string
     */
    protected function getLocalTablePrefix(): string
    {
        return '';
    }
}
