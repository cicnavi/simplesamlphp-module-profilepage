<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use Throwable;

abstract class AbstractMigration implements MigrationInterface
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;

    /**
     * @throws StoreException
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        try {
            $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        } catch (Throwable $exception) {
            $message = 'Could not create DBAL schema manager.';
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @throws MigrationException
     */
    protected function throwGenericMigrationException(string $contextDetails, Throwable $throwable): void
    {
        $message = sprintf(
            'There was an error running a migration class %s. Context details: %s. Error was: %s.',
            static::class,
            $contextDetails,
            $throwable->getMessage()
        );

        throw new MigrationException($message, (int) $throwable->getCode(), $throwable);
    }
}
