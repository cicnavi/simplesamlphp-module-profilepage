<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use Throwable;

class Store extends AbstractStore implements JobsStoreInterface
{
    protected string $prefixedTableNameJobs;
    protected string $prefixedTableNameFailedJobs;
    protected Repository $jobsRepository;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        Factory $factory,
        LoggerInterface $logger,
        Repository $jobsRepository = null
    ) {
        parent::__construct($moduleConfiguration, $factory, $logger);

        $this->prefixedTableNameJobs = $this->connection->preparePrefixedTableName(TableConstants::TABLE_NAME_JOBS);
        $this->prefixedTableNameFailedJobs = $this->connection
            ->preparePrefixedTableName(TableConstants::TABLE_NAME_FAILED_JOBS);

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

    public function getPrefixedTableNameJobs(): string
    {
        return $this->prefixedTableNameJobs;
    }

    public function getPrefixedTableNameFailedJobs(): string
    {
        return $this->prefixedTableNameFailedJobs;
    }

    /**
     * @throws StoreException
     */
    public static function build(ModuleConfiguration $moduleConfiguration): self
    {
        return new self(
            $moduleConfiguration,
            new Factory($moduleConfiguration, new Logger()),
            new Logger()
        );
    }
}
