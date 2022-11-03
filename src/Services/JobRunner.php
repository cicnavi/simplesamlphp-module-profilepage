<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Cicnavi\SimpleFileCache\SimpleFileCache;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\JobRunner\RateLimiter;
use SimpleSAML\Module\accounting\Services\JobRunner\State;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;

class JobRunner
{
    protected ModuleConfiguration $moduleConfiguration;
    protected SspConfiguration $sspConfiguration;
    protected LoggerInterface $logger;
    protected AuthenticationDataTrackerBuilder $authenticationDataTrackerBuilder;
    protected JobsStoreBuilder $jobsStoreBuilder;
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
    protected HelpersManager $helpersManager;
    protected ?\DateInterval $maximumExecutionTime;
    protected ?int $shouldPauseAfterNumberOfJobsProcessed;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        LoggerInterface $logger = null,
        HelpersManager $helpersManager = null,
        AuthenticationDataTrackerBuilder $authenticationDataTrackerBuilder = null,
        JobsStoreBuilder $jobsStoreBuilder = null,
        CacheInterface $cache = null,
        State $state = null,
        RateLimiter $rateLimiter = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->sspConfiguration = $sspConfiguration;
        $this->logger = $logger ?? new Logger();
        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->authenticationDataTrackerBuilder = $authenticationDataTrackerBuilder ??
            new AuthenticationDataTrackerBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);
        $this->jobsStoreBuilder = $jobsStoreBuilder ??
            new JobsStoreBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);

        $this->cache = $cache ?? $this->resolveCache();

        $this->jobRunnerId = $this->helpersManager->getRandomHelper()->getRandomInt();

        $this->state = $state ?? new State($this->jobRunnerId);

        $this->trackers = $this->resolveTrackers();
        $this->stateStaleThresholdInterval = new \DateInterval(self::STATE_STALE_THRESHOLD_INTERVAL);
        $this->rateLimiter = $rateLimiter ?? new RateLimiter();

        $this->maximumExecutionTime = $this->resolveMaximumExecutionTime();
        $this->shouldPauseAfterNumberOfJobsProcessed =
            $this->moduleConfiguration->getJobRunnerShouldPauseAfterNumberOfJobsProcessed();

        $this->registerInterruptHandler();
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

        $this->logger->debug('Run conditions validated.');

        $this->initializeCachedState();

        $jobsStore = $this->jobsStoreBuilder->build($this->moduleConfiguration->getJobsStoreClass());

        $jobsProcessedSincePause = 0;

        // We have a clean state, we can start processing.
        while ($this->shouldRun()) {
            try {
                /** @var ?Job $job */
                $job = $jobsStore->dequeue(Job::class);

                $this->updateCachedState($this->state);

                declare(ticks=1) {
                    // No new jobs at the moment....
                    if ($job === null) {
                        $this->state->addStatusMessage('No (more) jobs to process.');
                        // If in CLI, do the backoff pause, so we can continue working later.
                        if ($this->isCli()) {
                            $message = sprintf(
                                'Doing a backoff pause for %s seconds.',
                                $this->rateLimiter->getCurrentBackoffPauseInSeconds()
                            );
                            $this->logger->debug($message);
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
                }

                /** @var AuthenticationDataTrackerInterface $tracker */
                foreach ($this->trackers as $tracker) {
                    /** @var Job $job */
                    $tracker->process($job->getPayload());
                }

                $this->state->incrementSuccessfulJobsProcessed();

                /** @var Job $job */
                $successMessage = sprintf(
                    'Successfully processed job with ID %s.',
                    $job->getId() ?? '(N/A)'
                );
                $this->logger->debug($successMessage);
                $this->state->addStatusMessage($successMessage);

                // If the job runner friendly pausing is enabled, and if the number of jobs processed since the last
                // pause is greater than the configured value, do the pause.
                if (
                    $this->shouldPauseAfterNumberOfJobsProcessed !== null &&
                    $jobsProcessedSincePause > $this->shouldPauseAfterNumberOfJobsProcessed
                ) {
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
        // Enable this code to tick, which will enable it to catch CTRL-C signals and stop gracefully.
        declare(ticks=1) {
            if ($this->isMaximumExecutionTimeReached()) {
                $message = 'Maximum job runner execution time reached.';
                $this->logger->debug($message);
                $this->state->addStatusMessage($message);
                return false;
            }

            if ($this->state->getTotalJobsProcessed() > (PHP_INT_MAX - 1)) {
                $message = 'Maximum number of processed jobs reached.';
                $this->logger->debug($message);
                $this->state->addStatusMessage($message);
                return false;
            }

            try {
                $this->validateSelfState();
            } catch (\Throwable $exception) {
                $message = sprintf(
                    'Job runner state is not valid. Message was: %s',
                    $exception->getMessage()
                );
                $this->logger->warning($message);
                $this->state->addStatusMessage($message);
                return false;
            }
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
            $message = sprintf('Error initializing job runner state. Error was: %s.', $exception->getMessage());
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

            if ($cachedState->getIsGracefulInterruptInitiated()) {
                $message = 'Graceful job processing interrupt initiated.';
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
                $this->logger->warning('Stale cache encountered. Assuming no job runner is active.');
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
            $message = sprintf('Error setting job runner state. Error was: %s.', $exception->getMessage());
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

        /** @var string $trackerClass */
        foreach ($configuredTrackerClasses as $trackerClass) {
            $trackers[$trackerClass] = $this->authenticationDataTrackerBuilder->build($trackerClass);
        }

        return $trackers;
    }

    protected function isCli(): bool
    {
        return $this->helpersManager->getEnvironmentHelper()->isCli();
    }

    /**
     * Register interrupt handler. This makes it possible to stop job processing gracefully by
     * clearing the current state. It relies on pcntl extension, so to use this feature,
     * that extension has to be enabled.
     * @see https://www.php.net/manual/en/pcntl.installation.php
     * @return void
     */
    protected function registerInterruptHandler(): void
    {
        // pcntl won't be available in web server environment, so skip immediately.
        if (! $this->isCli()) {
            return;
        }

        // Extension pcntl doesn't come with PHP by default, so check if the proper function is available.
        if (! function_exists('pcntl_signal')) {
            $message = 'pcntl related functions not available, skipping registering interrupt handler.';
            $this->logger->info($message);
            $this->state->addStatusMessage($message);
            return;
        }

        pcntl_signal(SIGINT, [$this, 'handleInterrupt']);
        pcntl_signal(SIGTERM, [$this, 'handleInterrupt']);
    }

    /**
     * @throws Exception
     */
    protected function handleInterrupt(int $signal): void
    {
        $message = sprintf('Gracefully stopping job processing. Interrupt signal was %s.', $signal);
        $this->state->addStatusMessage($message);
        $this->logger->info($message);
        $this->state->setIsGracefulInterruptInitiated(true);
        $this->updateCachedState($this->state);
    }

    protected function resolveMaximumExecutionTime(): ?\DateInterval
    {
        $maximumExecutionTime = $this->moduleConfiguration->getJobRunnerMaximumExecutionTime();

        // If we are in CLI environment, we can safely use module configuration setting.
        if ($this->isCli()) {
            return $maximumExecutionTime;
        }

        // We are in a "web" environment, so take max execution time ini setting into account.
        $iniMaximumExecutionTimeSeconds = (int)floor((int)ini_get('max_execution_time') * 0.8);
        $iniMaximumExecutionTime = new \DateInterval('PT' . $iniMaximumExecutionTimeSeconds . 'S');

        // If the module setting is null (meaning infinite), use the ini setting.
        if ($maximumExecutionTime === null) {
            return $iniMaximumExecutionTime;
        }

        // Use the shorter interval from the two...
        $maximumExecutionTimeSeconds = $this->helpersManager
            ->getDateTimeHelper()
            ->convertDateIntervalToSeconds($maximumExecutionTime);

        if ($iniMaximumExecutionTimeSeconds < $maximumExecutionTimeSeconds) {
            $this->logger->debug('Using maximum execution time from INI setting since it is shorter.');
            return $iniMaximumExecutionTime;
        }

        return $maximumExecutionTime;
    }

    protected function isMaximumExecutionTimeReached(): bool
    {
        if ($this->maximumExecutionTime === null) {
            // Execution time is infinite.
            return false;
        }

        $startedAt = $this->state->getStartedAt();
        if ($startedAt === null) {
            // Processing has not even started yet.
            return false;
        }

        $maxDateTime = $startedAt->add($this->maximumExecutionTime);
        if ($maxDateTime > (new \DateTimeImmutable())) {
            // Maximum has not been reached yet.
            return false;
        }

        // Maximum has been reached.
        return true;
    }
}
