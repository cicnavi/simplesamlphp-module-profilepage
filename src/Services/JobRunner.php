<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use Cicnavi\SimpleFileCache\SimpleFileCache;
use SimpleSAML\Module\accounting\Services\JobRunner\State;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;

class JobRunner
{
    protected ModuleConfiguration $moduleConfiguration;
    protected SspConfiguration $sspConfiguration;
    protected LoggerInterface $logger;
    protected CacheInterface $cache;
    protected State $state;

    protected const CACHE_NAME = 'accounting-job-runner-cache';
    protected const CACHE_KEY_STATE = 'state';

    /**
     * Interval after which the state will be considered stale.
     */
    public const STATE_STALE_THRESHOLD_INTERVAL = 'PT9M';

    /**
     * @var int $jobRunnerId ID of the current job runner instance.
     */
    protected int $jobRunnerId;
    protected array $trackers;
    protected int $backoffPauseSeconds = 1;
    protected \DateInterval $stateStaleThresholdInterval;


    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        LoggerInterface $logger = null,
        CacheInterface $cache = null,
        State $state = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->sspConfiguration = $sspConfiguration;
        $this->logger = $logger ?? new Logger();

        $this->cache = $cache ?? $this->resolveCache();

        try {
            $this->jobRunnerId = random_int(PHP_INT_MIN, PHP_INT_MAX);
        } catch (\Throwable $exception) {
            $this->jobRunnerId = rand();
        }

        $this->state = $state ?? new State($this->jobRunnerId);

        $this->trackers = $this->resolveTrackers();
        $this->stateStaleThresholdInterval = new \DateInterval(self::STATE_STALE_THRESHOLD_INTERVAL);
    }

    /**
     * @throws Exception|\SimpleSAML\Module\accounting\Exceptions\StoreException
     */
    public function run(): State
    {
        if (! $this->areBasicRunConditionsMet()) {
            return $this->state;
        }

        $this->initializeCachedState();

        $jobsStore = (new JobsStoreBuilder($this->moduleConfiguration, $this->logger))
            ->build($this->moduleConfiguration->getJobsStoreClass());

        $jobsProcessedSincePause = 0;

        // We have a clean state, we can start processing.
        while ($this->shouldRun()) {
            try {
                /** @var ?Job $job */
                $job = $jobsStore->dequeue(Job::class);
                if ($job === null) {
                    // No new jobs, do the backoff pause.
                    $this->doBackoffPause();
                    $jobsProcessedSincePause = 0;
                    continue;
                }

                $this->resetBackoffPause();

                /** @var AuthenticationDataTrackerInterface $tracker */
                foreach ($this->trackers as $tracker) {
                    $tracker->process($job->getPayload());
                }

                $this->state->incrementSuccessfulJobsProcessed();

                // Just to not "kill" backend store, do the pause after n jobs.
                // TODO mivanci introduce configuration option for store friendly pausing.
                if ($jobsProcessedSincePause > 3) {
                    sleep(1);
                    $jobsProcessedSincePause = 0;
                } else  {
                    $jobsProcessedSincePause++;
                }
            } catch (\Throwable $exception) {
                $message = sprintf('Error while running jobs. Error was: %', $exception->getMessage());
                $this->logger->error($message, ['job' => $job]);
                $jobsStore->markFailedJob($job);
                $this->state->incrementFailedJobsProcessed();
            }

            $this->updateCachedState($this->state);
        }

        $this->clearCachedState();

        $this->state->setEndedAt(new \DateTimeImmutable());
        return $this->state;
    }

    /**
     */
    protected function shouldRun(): bool
    {
        // TODO mivanci check max execution time based on HTTP or CLI type run
        // TODO mivanci make configurable
        $maxExecutionTime = new \DateInterval('PT3S');
        if ($this->state->getStartedAt() !== null) {
            $maxDateTime = $this->state->getStartedAt()->add($maxExecutionTime);
            if ($this->state->getStartedAt() > $maxDateTime) {
                $this->logger->debug('Maximum job runner execution time reached.');
                return false;
            }
        }
        // TODO mivanci check max number of processed jobs in one run
        try {
            $this->validateState();
        } catch (\Throwable $exception) {
            $message = sprintf('Job runner state is not valid. Error was: %.', $exception->getMessage());
            $this->logger->warning($message);
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function initializeCachedState()
    {
        // Make sure that the state does not exist in the cache.
        try {
            if ($this->getCachedState() !== null) {
                throw new UnexpectedValueException('Job runner state already initialized.');
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error initializing job runner state. Error was: %.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        $startedAt = new \DateTimeImmutable();
        $this->state->setStartedAt($startedAt);

        $this->updateCachedState($this->state, $startedAt);
    }

    /**
     * @throws Exception
     */
    protected function validateState()
    {
        $cachedState = $this->getCachedState();

        // Validate state before start.
        if ($this->state->hasRunStarted() === false) {
            if ($cachedState !== null) {
                $message = 'Job run has not started, however cached state has already been initialized.';
                throw new Exception($message);
            }
        }

        // Validate state after start.
        if ($this->state->hasRunStarted() === true) {
            if ($cachedState === null) {
                $message = 'Job run has started, however cached state has not been initialized.';
                throw new Exception($message);
            }

            if ($cachedState->getJobRunnerId() !== $this->jobRunnerId) {
                $message = 'Current job runner ID differs from the ID in the cached state.';
                throw new Exception($message);
            }

            if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
                $message = 'Job runner cached state is stale, which means possible job runner process shutdown' .
                    ' without cached state clearing.';
                throw new Exception($message);
            }
        }

    }

    protected function isAnotherJobRunnerActive(): bool
    {
        try {
            $cachedState = $this->getCachedState();

            if ($cachedState === null) {
                return false;
            }

            // There is cached state, which would indicate that a job runner is active. However, make sure that the
            // state is not stale (which indicates that the runner was shutdown without state clearing).
            if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
                return true;
            }

            return $cachedState->getJobRunnerId() !== $this->jobRunnerId;
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error checking if another job runner is active. To play safe, we will assume true. ' .
                'Error was: %s',
                $exception->getMessage()
            );
            $this->logger->error($message);
            return true;
        }
    }

    /**
     * @throws Exception
     */
    protected function resolveCache(): SimpleFileCache
    {
        try {
            $this->logger->debug('Trying to initialize job runner cache using SSP datadir.');
            $cache = new SimpleFileCache(
                self::CACHE_NAME,
                $this->sspConfiguration->getPathValue('datadir')
            );
            $this->logger->debug('Successfully initialized cache using SSP datadir.');
            return $cache;
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error initializing job runner cache using datadir. Error was: %s',
                $exception->getMessage()
            );
            $this->logger->debug($message);
        }

        try {
            $this->logger->debug('Trying to initialize job runner cache using SSP tempdir.');
            $cache = new SimpleFileCache(
                self::CACHE_NAME,
                $this->sspConfiguration->getPathValue('tempdir')
            );
            $this->logger->debug('Successfully initialized job runner cache using SSP tempdir.');
            return $cache;
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error initializing job runner cache using tempdir. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->debug($message);
        }

        try {
            $this->logger->debug('Trying to initialize job runner cache using system tmp dir.');
            $cache = new SimpleFileCache(self::CACHE_NAME);
            $this->logger->debug('Successfully initialized cache using system tmp dir.');
            return $cache;
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error initializing job runner cache. Error was: %s.', $exception->getMessage()
            );
            $this->logger->debug($message);
            throw new Exception($message, (int)$exception->getCode(),$exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function clearCachedState()
    {
        try {
            $this->cache->delete(self::CACHE_KEY_STATE);
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf(
                'Error clearing job runner cache. Error was: %s.', $exception->getMessage()
            );
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function getCachedState(): ?State
    {
        try {
            /** @var ?State $state */
            $state = $this->cache->get(self::CACHE_KEY_STATE);
            if ($state instanceof State) {
                return $state;
            } else {
                return null;
            }
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf('Error getting job runner state from cache. Error was: %s', $exception->getMessage());
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function updateCachedState(State $state, \DateTimeImmutable $updatedAt = null): void
    {
        $updatedAt = $updatedAt ?? new \DateTimeImmutable();
        $state->setUpdatedAt($updatedAt);

        try {
            $this->cache->set(self::CACHE_KEY_STATE, $state);
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf('Error setting job runner state. Error was: %.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    protected function areBasicRunConditionsMet(): bool
    {
        // If accounting mode is not async, don't run.
        if (
            $this->moduleConfiguration->getAccountingProcessingType() !==
            ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS
        ) {
            $this->logger->warning(
                'Job runner called, however accounting mode is not ' .
                ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS
            );
            return false;
        }

        if (empty($this->trackers)) {
            $this->logger->debug('No trackers configured, skipping...');
            return false;
        }

        if ($this->isAnotherJobRunnerActive()) {
            $this->logger->debug('Another job runner is active, skipping...');
            return false;
        }

        return true;
    }

    protected function resolveTrackers(): array
    {
        $trackers = [];

        $configuredTrackerClasses = array_merge(
            [$this->moduleConfiguration->getDefaultDataTrackerAndProviderClass()],
            $this->moduleConfiguration->getAdditionalTrackers()
        );

        $trackerBuilder = new AuthenticationDataTrackerBuilder($this->moduleConfiguration, $this->logger);

        foreach ($configuredTrackerClasses as $trackerClass) {
            $trackers[$trackerClass] = $trackerBuilder->build($trackerClass);
        }

        return $trackers;
    }

    protected function doBackoffPause(): void
    {
        // TODO mivanci make configurable.
        $maxBackoffPauseSeconds = 60;

        $this->backoffPauseSeconds = $this->backoffPauseSeconds < $maxBackoffPauseSeconds ?
            $this->backoffPauseSeconds + $this->backoffPauseSeconds :
            $maxBackoffPauseSeconds;

        $this->logger->debug('Doing a backoff pause for ' . $this->backoffPauseSeconds . ' seconds');
        sleep($maxBackoffPauseSeconds);
    }

    protected function resetBackoffPause(): void
    {
        $this->backoffPauseSeconds = 1;
    }
}
