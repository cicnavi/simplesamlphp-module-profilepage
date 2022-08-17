<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;
use Throwable;

class JobsStore implements JobsStoreInterface
{
    public const TABLE_NAME_JOBS = 'jobs';
    public const TABLE_NAME_FAILED_JOBS = 'failed_jobs';

    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_PAYLOAD = 'payload';
    public const COLUMN_NAME_TYPE = 'type';
    public const COLUMN_NAME_CREATED_AT = 'created_at';

    public const COLUMN_LENGTH_TYPE = 1024;

    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected string $prefixedTableNameJobs;
    protected string $prefixedTableNameFailedJobs;
    protected Migrator $migrator;
    protected LoggerInterface $logger;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        Factory $factory,
        LoggerInterface $logger
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->connection = $factory->buildConnection($moduleConfiguration->getStoreConnection(self::class));
        $this->migrator = $factory->buildMigrator($this->connection);
        $this->logger = $logger;

        $this->prefixedTableNameJobs = $this->connection->preparePrefixedTableName(self::TABLE_NAME_JOBS);
        $this->prefixedTableNameFailedJobs = $this->connection->preparePrefixedTableName(self::TABLE_NAME_FAILED_JOBS);
    }

    /**
     * @throws StoreException
     */
    public function enqueue(JobInterface $job, string $type = null): void
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $payload = $job->getPayload();
        $type = $this->validateType($type ?? get_class($job));

        $queryBuilder->insert($this->prefixedTableNameJobs)
            ->values(
                [
                    self::COLUMN_NAME_PAYLOAD => ':' . self::COLUMN_NAME_PAYLOAD,
                    self::COLUMN_NAME_TYPE => ':' . self::COLUMN_NAME_TYPE,
                    self::COLUMN_NAME_CREATED_AT => ':' . self::COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    self::COLUMN_NAME_PAYLOAD => serialize($payload),
                    self::COLUMN_NAME_TYPE => $type,
                    self::COLUMN_NAME_CREATED_AT => new DateTimeImmutable(),
                ],
                [
                    self::COLUMN_NAME_PAYLOAD => Types::TEXT,
                    self::COLUMN_NAME_TYPE => Types::STRING,
                    self::COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Could not enqueue job (%s)', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    protected function validateType(string $type): string
    {
        if (mb_strlen($type) > self::COLUMN_LENGTH_TYPE) {
            throw new StoreException(
                sprintf('String length for type column exceeds %s limit.', self::COLUMN_LENGTH_TYPE)
            );
        }

        return $type;
    }

    /**
     * @throws StoreException
     */
    public function dequeue(string $type = null): ?JobInterface
    {
        $job = null;

        try {
            // Check if there are any jobs in the store...
            while ($row = $this->getNext($type)->fetchAssociative()) {
                // We have a row. Let's create a row job instance, which will take care of validation and make it easier
                // to work with instead of array.
                $rawJob = new RawJob($row, $this->connection->dbal()->getDatabasePlatform());
                // Let's try to delete this job from the store, so it can't be fetched again.
                $numberOfAffectedRows = $this->delete($rawJob->getId());
                if ($numberOfAffectedRows === 0) {
                    // It seems that this job has already been dequeued in the meantime. Try to get next job again.
                    continue;
                }
                // Job is deleted, meaning it is now dequeued and we can return a new job instance.
                // If a valid type is declared, let's try to instantiate a job of that specific type.
                if ($type !== null && class_exists($type) && is_subclass_of($type, JobInterface::class)) {
                    $job = (new ReflectionClass($type))->newInstance($rawJob->getPayload());
                } else {
                    // No (valid) job type, so generic job will do...
                    $job = new GenericJob($rawJob->getPayload());
                }

                // We have found and dequeued a job, so finish with the search.
                break;
            }
        } catch (Throwable $exception) {
            throw new StoreException(
                'Error while trying to dequeue a job.',
                (int) $exception->getCode(),
                $exception
            );
        }

        return $job;
    }

    /**
     * @throws StoreException
     */
    public function getNext(string $type = null): Result
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        /**
         * @psalm-suppress TooManyArguments - providing array or null is deprecated
         */
        $queryBuilder->select(
            self::COLUMN_NAME_ID,
            self::COLUMN_NAME_PAYLOAD,
            self::COLUMN_NAME_TYPE,
            self::COLUMN_NAME_CREATED_AT
        )
            ->from($this->prefixedTableNameJobs)
            ->orderBy(self::COLUMN_NAME_ID)
            ->setMaxResults(1);

        if ($type !== null) {
            $queryBuilder->where(self::COLUMN_NAME_TYPE . ' = ' . $queryBuilder->createNamedParameter($type));
        }

        try {
            $result = $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = 'Error while trying to execute query to get next available job.';
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $result;
    }

    protected function delete(int $id): int
    {
        return (int)$this->connection->dbal()
            ->delete(
                $this->prefixedTableNameJobs,
                [self::COLUMN_NAME_ID => $id],
                [self::COLUMN_NAME_ID => Types::BIGINT]
            );
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function runSetup(): void
    {
        if ($this->migrator->needsSetup()) {
            $this->migrator->runSetup();
        }

        if (!$this->areAllMigrationsImplemented()) {
            $this->migrator->runNonImplementedMigrationClasses(
                $this->getMigrationsDirectory(),
                $this->getMigrationsNamespace()
            );
        }
    }

    /**
     * @throws StoreException
     */
    public function needsSetup(): bool
    {
        // ... if the migrator itself needs setup.
        if ($this->migrator->needsSetup()) {
            return true;
        }

        // ... if JobsStore migrations need to run
        if (!$this->areAllMigrationsImplemented()) {
            return true;
        }

        return false;
    }

    public function areAllMigrationsImplemented(): bool
    {
        return !$this->migrator->hasNonImplementedMigrationClasses(
            $this->getMigrationsDirectory(),
            $this->getMigrationsNamespace()
        );
    }

    protected function getMigrationsDirectory(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR .
            (new ReflectionClass($this))->getShortName() . DIRECTORY_SEPARATOR .
            AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    protected function getMigrationsNamespace(): string
    {
        return self::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    public function getPrefixedTableNameJobs(): string
    {
        return $this->prefixedTableNameJobs;
    }

    public function getPrefixedTableNameFailedJobs(): string
    {
        return $this->prefixedTableNameFailedJobs;
    }
}
