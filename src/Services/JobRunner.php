<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Cicnavi\SimpleFileCache\Exceptions\CacheException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use Cicnavi\SimpleFileCache\SimpleFileCache;
use SimpleSAML\Module\accounting\Services\JobRunner\State;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;

class JobRunner
{
    protected ModuleConfiguration $moduleConfiguration;
    protected SspConfiguration $sspConfiguration;
    protected LoggerInterface $logger;
    protected CacheInterface $cache;

    protected bool $runStarted = false;


    protected const CACHE_NAME = 'accounting-job-runner-cache';
    protected const CACHE_KEY_STATE = 'state';

    protected const STATE_KEY_LAST_UPDATE_TIMESTAMP = 'last-update';
    protected const STATE_KEY_JOB_RUNNER_ID = 'job-runner-id';

    public const STATE_THRESHOLD_INTERVAL = 'PT10M';

    protected int $jobRunnerId;


    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        LoggerInterface $logger = null,
        CacheInterface $cache = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->sspConfiguration = $sspConfiguration;
        $this->logger = $logger ?? new Logger();

        $this->cache = $cache ?? $this->resolveCache($this->sspConfiguration);

        try {
            $this->jobRunnerId = random_int(PHP_INT_MIN, PHP_INT_MAX);
        } catch (\Throwable $exception) {
            $this->jobRunnerId = rand();
        }
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        // TODO mivanci continue implementing this..
        if (! $this->shouldRun()) {
            return;
        }

        die(var_dump($this->cache->get(self::CACHE_KEY_STATE)));
        $this->runStarted = true;
        $this->initializeState();
    }

    protected function shouldRun(): bool
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

        if ($this->isAnotherJobRunnerActive()) {
            $this->logger->debug('Another job runner is active, skipping...');
            return false;
        }

        try {
            $this->validateState();
        } catch (\Throwable $exception) {
            $message = sprintf('Job runner state is not valid. Error was: %.', $exception->getMessage());
            $this->logger->warning($message);
            $this->clearState();
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function initializeState()
    {
        try {
            if ($this->cache->has(self::CACHE_KEY_STATE)) {
                throw new UnexpectedValueException('Job runner state already initialized.');
            }
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf('Error initializing job runner state. Error was: %.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        try {
            $this->cache->set(self::CACHE_KEY_STATE, $this->getFreshStateInstance());
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf('Error setting job runner state. Error was: %.', $exception->getMessage());
            $this->logger->error($message);
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    protected function validateState()
    {
        $state = $this->getState();

        // Validate state before start.
        if ($this->runStarted === false) {
            if ($state !== null) {
                $message = 'Job run has not started, however state has already been initialized.';
                throw new Exception($message);
            }
        }

        // Validate state after start.
        if ($this->runStarted === true) {
            if ($state === null) {
                $message = 'Job run has started, however state has not been initialized.';
                throw new Exception($message);
            }

            if ($state->getJobRunnerId() !== $this->jobRunnerId) {
                $message = 'Current job runner ID differs from the ID in the state.';
                throw new Exception($message);
            }

            $threshold = (new \DateTime())->sub(new \DateInterval(self::STATE_THRESHOLD_INTERVAL));
            if ($state->getUpdatedAt() < $threshold) {
                $message = 'Job runner state is stale.';
                throw new Exception($message);
            }
        }

    }

    protected function getFreshStateInstance(): State
    {
        return new State($this->jobRunnerId);
    }

    protected function isAnotherJobRunnerActive(): bool
    {
        try {
            $state = $this->getState();

            if ($state === null) {
                return false;
            }

            return $state->getJobRunnerId() !== $this->jobRunnerId;
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

    protected function resolveCache(SspConfiguration $sspConfiguration)
    {
        try {
            $this->logger->debug('Trying to initialize cache using SSP datadir.');
            $cache = new SimpleFileCache(
                self::CACHE_NAME,
                $this->sspConfiguration->getPathValue('datadir')
            );
            $this->logger->debug('Successfully initialized cache using SSP datadir.');
            return $cache;
        } catch (\Throwable $exception) {
            $message = sprintf('Error initializing cache using datadir. Error was: %s', $exception->getMessage());
            $this->logger->debug($message);
        }

        try {
            $this->logger->debug('Trying to initialize cache using SSP tempdir.');
            $cache = new SimpleFileCache(
                self::CACHE_NAME,
                $this->sspConfiguration->getPathValue('tempdir')
            );
            $this->logger->debug('Successfully initialized cache using SSP tempdir.');
            return $cache;
        } catch (\Throwable $exception) {
            $message = sprintf('Error initializing cache using tempdir. Error was: %s.', $exception->getMessage());
            $this->logger->debug($message);
        }

        try {
            $this->logger->debug('Trying to initialize cache using system tmp dir.');
            $cache = new SimpleFileCache(self::CACHE_NAME);
            $this->logger->debug('Successfully initialized cache using system tmp dir.');
            return $cache;
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error initializing cache. Error was: %s.', $exception->getMessage()
            );
            $this->logger->debug($message);
            throw new Exception($message, (int)$exception->getCode(),$exception);
        }
    }

    protected function clearState()
    {
        $this->cache->delete(self::CACHE_KEY_STATE);
    }

    protected function getState(): ?State
    {
        try {
            /** @var ?State $state */
            $state = $this->cache->get(self::CACHE_KEY_STATE);
            return $state;
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf('Error getting state from cache. Error was: %s', $exception->getMessage());
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }
    }
}
