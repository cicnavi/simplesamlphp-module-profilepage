<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Traits\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store\TableConstants;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use Throwable;

trait DeletableAuthenticationEventsTrait
{
    /**
     * @throws StoreException
     */
    public function deleteAuthenticationEventsOlderThan(DateTimeImmutable $dateTime): void
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $queryBuilder->delete($this->tableNameAuthenticationEvent)
                ->where(
                    $queryBuilder->expr()->lt(
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                        $queryBuilder->createNamedParameter($dateTime->getTimestamp(), Types::BIGINT)
                    )
                )->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to delete old authentication events. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
