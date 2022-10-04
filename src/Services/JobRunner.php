<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
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
    public const STATE_STALE_THRESHOLD_INTERVAL = 'PT5M';

    /**
     * @var int $jobRunnerId ID of the current job runner instance.
     */
    protected int $jobRunnerId;
    protected array $trackers;
    protected \DateInterval $stateStaleThresholdInterval;
    protected RateLimiter $rateLimiter;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        LoggerInterface $logger = null,
        CacheInterface $cache = null,
        State $state = null,
        RateLimiter $rateLimiter = null
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
        $this->rateLimiter = $rateLimiter ?? new RateLimiter();
    }

    /**
     * @throws Exception|StoreException
     */
    public function run(): State
    {
        try {
            $this->validatePreRunState();
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Pre-run state validation failed. Clearing cached state and continuing. Error was %s',
                $exception->getMessage()
            );
            $this->logger->warning($message);
            $this->state->addStatusMessage($message);
            $this->clearCachedState();
        }

        try {
            $this->validateRunConditions();
        } catch (\Throwable $exception) {
            $message = sprintf('Run conditions are not met, stopping. Reason was: %s', $exception->getMessage());
            $this->logger->info($message);
            $this->state->addStatusMessage($message);
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

                $this->updateCachedState($this->state);

                // No new jobs at the moment....
                if ($job === null) {
                    $this->state->addStatusMessage('No (more) jobs to process.');
                    // If in CLI, do the backoff pause, so we can continue working later.
                    if ($this->isCli()) {
                        $message = sprintf(
                            'Doing a backoff pause for %s seconds.',
                            $this->rateLimiter->getCurrentBackoffPauseInSeconds()
                        );
                        $this->state->addStatusMessage($message);
                        $this->rateLimiter->doBackoffPause();
                        $jobsProcessedSincePause = 0;
                        continue;
                    } else {
                        // Since this is a web run, we will break immediately, so we can return HTTP response.
                        break;
                    }
                }

                // We have a job...
                $this->rateLimiter->resetBackoffPause();

                /** @var AuthenticationDataTrackerInterface $tracker */
                foreach ($this->trackers as $tracker) {
                    $tracker->process($job->getPayload());
                }

                $this->state->incrementSuccessfulJobsProcessed();
                $successMessage = sprintf(
                    'Successfully processed job with ID %s.',
                    $job->getId() ?? '(N/A)'
                );
                $this->state->addStatusMessage($successMessage);

                // Just to try not to "kill" backend store, do the pause after n jobs.
                // TODO mivanci introduce configuration option for store friendly pausing.
                if ($jobsProcessedSincePause > 3) {
                    $this->rateLimiter->doPause();
                    $jobsProcessedSincePause = 0;
                } else {
                    $jobsProcessedSincePause++;
                }
            } catch (\Throwable $exception) {
                $message = sprintf('Error while processing jobs. Error was: %', $exception->getMessage());
                $context = [];
                if (isset($job)) {
                    $context = ['job' => $job];
                    $jobsStore->markFailedJob($job);
                }
                $this->logger->error($message, $context);
                $this->state->incrementFailedJobsProcessed();
                $this->state->addStatusMessage($message);
            }
        }

        $this->clearCachedState();

        $this->state->setEndedAt(new \DateTimeImmutable());
        return $this->state;
    }

    /**
     */
    protected function shouldRun(): bool
    {
        // TODO mivanci change default to null, make configurable and nullable if CLI
        $durationSeconds = 60;

        if (!$this->isCli()) {
            $maxSeconds = (int)floor((int)ini_get('max_execution_time') * 0.8);
            if ($maxSeconds < $durationSeconds) {
                $durationSeconds = $maxSeconds;
            }
        }

        $maxExecutionTime = new \DateInterval('PT' . $durationSeconds . 'S');

        $startedAt = $this->state->getStartedAt();
        if ($startedAt !== null) {
            $maxDateTime = $startedAt->add($maxExecutionTime);

            if ((new \DateTimeImmutable()) > $maxDateTime) {
                $this->logger->debug('Maximum job runner execution time reached.');
                return false;
            }
        }
        // TODO mivanci make configurable.
        if ($this->state->getTotalJobsProcessed() > PHP_INT_MAX - 1) {
            return false;
        }

        try {
            $this->validateSelfState();
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
    protected function initializeCachedState(): void
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
    protected function validatePreRunState(): void
    {
        $cachedState = $this->getCachedState();

        // Empty state means that no other job runner is active.
        if ($cachedState === null) {
            return;
        }

        if ($cachedState->getJobRunnerId() === $this->jobRunnerId) {
            $message = 'Job runner ID in cached state same as new ID.';
            $this->logger->error($message);
            throw new Exception($message);
        }

        if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
            $message = 'Stale state encountered.';
            $this->logger->warning($message);
            throw new Exception($message);
        }
    }

    /**
     * @throws Exception
     */
    protected function validateSelfState(): void
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
            // state is not stale (which indicates that the runner was shutdown without state clearing). If stale,
            // this means that the job runner is not active.
            if ($cachedState->isStale($this->stateStaleThresholdInterval)) {
                return false;
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
                'Error initializing job runner cache. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->debug($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function clearCachedState(): void
    {
        /** @psalm-suppress InvalidCatch */
        try {
            $this->cache->delete(self::CACHE_KEY_STATE);
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf(
                'Error clearing job runner cache. Error was: %s.',
                $exception->getMessage()
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
        /** @psalm-suppress InvalidCatch */
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

        /** @psalm-suppress InvalidCatch */
        try {
            $this->cache->set(self::CACHE_KEY_STATE, $state);
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf('Error setting job runner state. Error was: %.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function validateRunConditions(): void
    {
        if (
            $this->moduleConfiguration->getAccountingProcessingType() !==
            ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS
        ) {
            $message = 'Job runner called, however accounting mode is not ' .
                ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS;
            $this->logger->warning($message);
            throw new Exception($message);
        }

        if (empty($this->trackers)) {
            $message = 'No trackers configured.';
            $this->logger->debug($message);
            throw new Exception($message);
        }

        if ($this->isAnotherJobRunnerActive()) {
            $message = 'Another job runner is active.';
            $this->logger->debug($message);
            throw new Exception($message);
        }
    }

    /**
     * @throws Exception
     */
    protected function resolveTrackers(): array
    {
        $trackers = [];

        $configuredTrackerClasses = array_merge(
            [$this->moduleConfiguration->getDefaultDataTrackerAndProviderClass()],
            $this->moduleConfiguration->getAdditionalTrackers()
        );

        $trackerBuilder = new AuthenticationDataTrackerBuilder($this->moduleConfiguration, $this->logger);

        /** @var string $trackerClass */
        foreach ($configuredTrackerClasses as $trackerClass) {
            $trackers[$trackerClass] = $trackerBuilder->build($trackerClass);
        }

        return $trackers;
    }

    protected function isCli(): bool
    {
        return http_response_code() === false;
    }
}
