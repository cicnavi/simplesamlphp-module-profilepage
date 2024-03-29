<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Traits\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\TableConstants;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use Throwable;

trait DeletableConnectedServicesTrait
{
    /**
     * @throws StoreException
     */
    public function deleteConnectedServicesOlderThan(DateTimeImmutable $dateTime): void
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $queryBuilder->delete($this->tableNameConnectedService)
                ->where(
                    $queryBuilder->expr()->lt(
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                        $queryBuilder->createNamedParameter($dateTime->getTimestamp(), Types::BIGINT)
                    )
                )->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to delete old connected services. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
