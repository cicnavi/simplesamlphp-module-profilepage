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
        $this->cache = $cache ?? new SimpleFileCache(
            self::CACHE_NAME,
            $this->sspConfiguration->getPathValue('datadir') ??
                $sspConfiguration->getPathValue('tempdir') ??
                null
        );

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
        die('tu');
        if (! $this->shouldRun()) {
            return;
        }

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

    protected function validateState()
    {
        if ($this->runStarted === false && $this->cache->has(self::CACHE_KEY_STATE)) {
            $message = 'Job run has not started, however state has already been initialized.';
        }
    }

    protected function getFreshStateInstance(): State
    {
        return new State($this->jobRunnerId);
    }

    protected function isAnotherJobRunnerActive(): bool
    {
        if (! $this->cache->has(self::CACHE_KEY_STATE)) {
            return false;
        }

        try {
            $state = $this->cache->get(self::CACHE_KEY_STATE);
            die(var_dump($state));
        } catch (\Throwable | InvalidArgumentException $exception) {
            $message = sprintf(
                'Error checking if another job runner is active. To play safe, we will assume true. ' .
                'Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message);
            return true;
        }
    }
}
