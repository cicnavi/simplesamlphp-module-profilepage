<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository;
use Throwable;

class Store implements JobsStoreInterface
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
    protected Repository $jobsRepository;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        Factory $factory,
        LoggerInterface $logger,
        Repository $jobsRepository = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->connection = $factory->buildConnection($moduleConfiguration->getStoreConnection(self::class));
        $this->migrator = $factory->buildMigrator($this->connection);
        $this->logger = $logger;

        $this->prefixedTableNameJobs = $this->connection->preparePrefixedTableName(self::TABLE_NAME_JOBS);
        $this->prefixedTableNameFailedJobs = $this->connection->preparePrefixedTableName(self::TABLE_NAME_FAILED_JOBS);

        $this->jobsRepository = $jobsRepository ??
            new Repository($this->connection, $this->prefixedTableNameJobs, $this->logger);
    }

    /**
     * @throws StoreException
     */
    public function enqueue(JobInterface $job): void
    {
        $this->jobsRepository->insert($job);
    }

    /**
     * @throws StoreException
     */
    public function dequeue(string $type = null): ?JobInterface
    {
        /** @noinspection PhpUnusedLocalVariableInspection - psalm reports possibly undefined variable */
        $job = null;
        $attempts = 0;
        $maxDeleteAttempts = 3;

        try {
            // Check if there are any jobs in the store...
            while (($job = $this->jobsRepository->getNext($type)) !== null) {
                // We have job instance.
                $jobId = $job->getId();

                if ($jobId === null) {
                    throw new UnexpectedValueException('Retrieved job does not contain ID.');
                }

                $attempts++;

                // Let's try to delete this job from the store, so it can't be fetched again.
                if ($this->jobsRepository->delete($jobId) === false) {
                    // It seems that this job has already been deleted in the meantime.
                    // Check if this happened before. If threshold is reached, throw.
                    // Otherwise, try to get next job again.
                    if ($attempts > $maxDeleteAttempts) {
                        $message = 'Job retrieval was successful, however it was deleted in the meantime.';
                        throw new StoreException($message);
                    }

                    continue;
                }

                // We have found and dequeued a job, so finish with the search.
                break;
            }
        } catch (Throwable $exception) {
            throw new StoreException(
                'Error while trying to dequeue a job.',
                (int)$exception->getCode(),
                $exception
            );
        }

        return $job;
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

        // ... if Store migrations need to run
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

    public static function build(ModuleConfiguration $moduleConfiguration): self
    {
        return new self(
            $moduleConfiguration,
            new Factory($moduleConfiguration, new Logger()),
            new Logger()
        );
    }
}
