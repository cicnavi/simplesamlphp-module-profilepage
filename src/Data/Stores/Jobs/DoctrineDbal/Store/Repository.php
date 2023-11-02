<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;

use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Interfaces\SerializerInterface;
use Throwable;

class Repository
{
    protected array $validJobsTableNames = [];

    protected string $tableName;

    /**
     * @throws StoreException
     */
    public function __construct(
        protected Connection $connection,
        string $tableName,
        protected LoggerInterface $logger,
        protected SerializerInterface $serializer,
    ) {
        $this->prepareValidJobsTableNames();

        $this->validateTableName($tableName);

        $this->tableName = $tableName;
    }

    protected function prepareValidJobsTableNames(): void
    {
        $this->validJobsTableNames[] = $this->connection
            ->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOB);
        $this->validJobsTableNames[] = $this->connection
            ->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOB_FAILED);
    }

    /**
     * @throws StoreException
     */
    public function insert(JobInterface $job): void
    {
        $this->validateType($job->getType());

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $queryBuilder->insert($this->tableName)
            ->values(
                [
                    Store\TableConstants::COLUMN_NAME_PAYLOAD => ':' . Store\TableConstants::COLUMN_NAME_PAYLOAD,
                    Store\TableConstants::COLUMN_NAME_TYPE => ':' . Store\TableConstants::COLUMN_NAME_TYPE,
                    Store\TableConstants::COLUMN_NAME_CREATED_AT => ':' . Store\TableConstants::COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    Store\TableConstants::COLUMN_NAME_PAYLOAD => $this->serializer->do($job->getRawState()),
                    Store\TableConstants::COLUMN_NAME_TYPE => $job->getType(),
                    Store\TableConstants::COLUMN_NAME_CREATED_AT => $job->getCreatedAt()->getTimestamp(),
                ],
                [
                    Store\TableConstants::COLUMN_NAME_PAYLOAD => Types::TEXT,
                    Store\TableConstants::COLUMN_NAME_TYPE => Types::STRING,
                    Store\TableConstants::COLUMN_NAME_CREATED_AT => Types::BIGINT
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Could not insert job (%s)', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @param string|null $type
     * @return ?JobInterface
     * @throws StoreException
     */
    public function getNext(string $type = null): ?JobInterface
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        /**
         * @psalm-suppress TooManyArguments - providing array or null is deprecated
         */
        $queryBuilder->select(
            Store\TableConstants::COLUMN_NAME_ID,
            Store\TableConstants::COLUMN_NAME_PAYLOAD,
            Store\TableConstants::COLUMN_NAME_TYPE,
            Store\TableConstants::COLUMN_NAME_CREATED_AT
        )
            ->from($this->tableName)
            ->orderBy(Store\TableConstants::COLUMN_NAME_ID)
            ->setMaxResults(1);

        if ($type !== null) {
            $queryBuilder->where(
                Store\TableConstants::COLUMN_NAME_TYPE . ' = ' . $queryBuilder->createNamedParameter($type)
            );
        }

        try {
            $result = $queryBuilder->executeQuery();
            $row = $result->fetchAssociative();
        } catch (Throwable $exception) {
            $message = 'Error while trying to execute query to get next available job.';
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        if ($row === false) {
            return null;
        }

        try {
            $rawJob = new RawJob($row, $this->connection->dbal()->getDatabasePlatform(), $this->serializer);
            $rawJobType = $rawJob->getType();

            // Try to create a specific job type. Otherwise, create a generic one.
            if (class_exists($rawJobType) && is_subclass_of($rawJobType, JobInterface::class)) {
                $job = (new ReflectionClass($rawJobType))
                    ->newInstance($rawJob->getPayload(), $rawJob->getId(), $rawJob->getCreatedAt());
            } else {
                // No (valid) job type, so generic one will do...
                $job = new GenericJob($rawJob->getPayload(), $rawJob->getId(), $rawJob->getCreatedAt());
            }
        } catch (Throwable $exception) {
            $message = 'Could not create a job instance.';
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $job;
    }

    /**
     * @throws StoreException
     */
    public function delete(int $id): bool
    {
        try {
            $numberOfAffectedRows = (int)$this->connection->dbal()
                ->delete(
                    $this->tableName,
                    [Store\TableConstants::COLUMN_NAME_ID => $id],
                    [Store\TableConstants::COLUMN_NAME_ID => Types::BIGINT]
                );
        } catch (Throwable $exception) {
            $message = sprintf('Error while trying to delete a job with ID %s.', $id);
            $this->logger->error($message, compact('id'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        if ($numberOfAffectedRows === 0) {
            return false;
        }

        return true;
    }

    /**
     * @throws StoreException
     */
    protected function validateTableName(string $tableName): void
    {
        if (!in_array($tableName, $this->validJobsTableNames)) {
            throw new StoreException(
                sprintf('Table %s is not valid table for storing jobs.', $tableName)
            );
        }
    }

    /**
     * @throws StoreException
     */
    protected function validateType(string $type): void
    {
        if (mb_strlen($type) > Store\TableConstants::COLUMN_TYPE_LENGTH) {
            throw new StoreException(
                sprintf('String length for type column exceeds %s limit.', Store\TableConstants::COLUMN_TYPE_LENGTH)
            );
        }
    }
}
