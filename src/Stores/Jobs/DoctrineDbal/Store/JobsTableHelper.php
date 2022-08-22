<?php

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

class JobsTableHelper
{
    /**
     * @throws StoreException
     */
    public static function prepareJobsTable(string $tableName): Table
    {
        try {
            $table = new Table($tableName);

            $table->addColumn(Store::COLUMN_NAME_ID, Types::BIGINT)
                ->setUnsigned(true)
                ->setAutoincrement(true);

            $table->addColumn(Store::COLUMN_NAME_TYPE, Types::STRING)
                ->setLength(Store::COLUMN_LENGTH_TYPE);

            $table->addColumn(Store::COLUMN_NAME_PAYLOAD, Types::TEXT);
            $table->addColumn(Store::COLUMN_NAME_CREATED_AT, Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey([Store::COLUMN_NAME_ID]);

            return $table;
        } catch (\Throwable $exception) {
            $message = \sprintf('Error creating jobs table instance. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
