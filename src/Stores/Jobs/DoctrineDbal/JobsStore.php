<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal;

use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Entities\AuthenticationEvent\Job;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Jobs\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;

class JobsStore implements JobsStoreInterface
{
    public const TABLE_NAME_JOBS = 'jobs';
    public const TABLE_NAME_FAILED_JOBS = 'failed_jobs';

    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_PAYLOAD = 'payload';
    public const COLUMN_NAME_CREATED_AT = 'created_at';

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
    public function enqueue(AbstractJob $job): void
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $queryBuilder->insert($this->prefixedTableNameJobs)
            ->values(
                [
                    self::COLUMN_NAME_PAYLOAD => ':' . self::COLUMN_NAME_PAYLOAD,
                    self::COLUMN_NAME_CREATED_AT => ':' . self::COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    self::COLUMN_NAME_PAYLOAD => serialize($job->getPayload()),
                    self::COLUMN_NAME_CREATED_AT => new \DateTimeImmutable(),
                ],
                [
                    self::COLUMN_NAME_PAYLOAD => Types::TEXT,
                    self::COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (\Throwable $exception) {
            $message = sprintf('Could not enqueue job (%s)', $exception->getMessage());
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
    }

    public function dequeue(): AbstractJob
    {
        // TODO: Implement dequeue() method.
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        /**
         * @psalm-suppress TooManyArguments providing array or null is deprecated
         */
        $queryBuilder->select(
            self::COLUMN_NAME_ID,
            self::COLUMN_NAME_PAYLOAD,
            self::COLUMN_NAME_CREATED_AT
        )
            ->from($this->prefixedTableNameJobs)
            ->setMaxResults(1);
        return new Job(new AuthenticationEvent([]));
    }

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
            (new \ReflectionClass($this))->getShortName() . DIRECTORY_SEPARATOR .
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
