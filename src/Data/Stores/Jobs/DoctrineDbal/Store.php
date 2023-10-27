<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use Throwable;

class Store extends AbstractStore implements JobsStoreInterface
{
    protected string $prefixedTableNameJobs;
    protected string $prefixedTableNameFailedJobs;
    protected Repository $jobsRepository;
    protected Repository $failedJobsRepository;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        Factory $connectionFactory = null,
        Repository $jobsRepository = null,
        Repository $failedJobsRepository = null
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionKey, $connectionType, $connectionFactory);

        $this->prefixedTableNameJobs = $this->connection->preparePrefixedTableName(TableConstants::TABLE_NAME_JOB);
        $this->prefixedTableNameFailedJobs = $this->connection
            ->preparePrefixedTableName(TableConstants::TABLE_NAME_JOB_FAILED);

        $this->jobsRepository = $jobsRepository ??
            new Repository($this->connection, $this->prefixedTableNameJobs, $this->logger, $this->serializer);

        $this->failedJobsRepository = $failedJobsRepository ??
            new Repository($this->connection, $this->prefixedTableNameFailedJobs, $this->logger, $this->serializer);
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
     * @throws Exception|Exception
     */
    public function dequeue(string $type): ?JobInterface
    {
        /** @noinspection PhpUnusedLocalVariableInspection - psalm reports possibly undefined variable */
        $job = null;
        $attempts = 0;
        $maxDeleteAttempts = 3;
        $this->connection->dbal()->getTransactionIsolation();

        // Do the dequeue without using transactions, since the underlying database engine might not support it
        // (for example, MyISAM engine in MySQL database).
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
                    $message = sprintf(
                        'Job retrieval was successful, however it was deleted in the meantime. Attempt: %s',
                        $attempts
                    );
                    $this->logger->warning($message, ['jobId' => $jobId]);
                    if ($attempts > $maxDeleteAttempts) {
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

    public function getPrefixedTableNameJobs(): string
    {
        return $this->prefixedTableNameJobs;
    }

    public function getPrefixedTableNameFailedJobs(): string
    {
        return $this->prefixedTableNameFailedJobs;
    }

    /**
     * Build store instance.
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     * @param string|null $connectionKey
     * @return self
     * @throws StoreException
     */
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self {
        return new self(
            $moduleConfiguration,
            $logger,
            $connectionKey
        );
    }

    /**
     * @throws StoreException
     */
    public function markFailedJob(JobInterface $job): void
    {
        $this->failedJobsRepository->insert($job);
    }
}
