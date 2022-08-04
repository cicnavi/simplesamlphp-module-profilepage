<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;

abstract class AbstractMigration implements MigrationInterface
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
    }

    /**
     * @throws MigrationException
     */
    protected function throwGenericMigrationException(string $contextDetails, \Throwable $throwable): void
    {
        $message = sprintf(
            'There was an error running a migration class %s. Context details: %s. Error was: %s.',
            self::class,
            $contextDetails,
            $throwable->getMessage()
        );

        throw new MigrationException($message, (int) $throwable->getCode(), $throwable);
    }
}
