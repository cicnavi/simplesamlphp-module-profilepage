<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis;

use Psr\Log\LoggerInterface;
use Redis;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Bases\AbstractStore;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;
use Throwable;

class RedisStore extends AbstractStore implements JobsStoreInterface
{
    public const LIST_KEY_JOB = 'job';
    public const LIST_KEY_JOB_FAILED = 'job_failed';

    protected Redis $redis;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        Redis $redis = null
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionKey, $connectionType);
        $this->redis = $redis ?? new Redis();
        $connectionParameters = $this->getConnectionParameters();

        try {
            if (!$this->redis->isConnected()) {
                $this->redis->connect(
                    (string)($connectionParameters['host'] ?? ''),
                    (int)($connectionParameters['port'] ?? 6379),
                    (float)($connectionParameters['connectTimeout'] ?? 0.0),
                    null,
                    (int)($connectionParameters['retryInterval'] ?? 0),
                    (int)($connectionParameters['readTimeout'] ?? 0),
                );
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error trying to connect to Redis DB. Error was: %s', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }

        try {
            if (isset($connectionParameters['auth'])) {
                $this->redis->auth($connectionParameters['auth']);
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error trying to set auth parameter for Redis. Error was: %s', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message);
        }

        try {
            $this->redis->setOption(Redis::OPT_PREFIX, $connectionParameters['keyPrefix'] ?? 'ssp_accounting:');
        } catch (Throwable $exception) {
            $message = sprintf('Could not set key prefix for Redis. Error was: %s', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     * @throws StoreException
     */
    public function enqueue(JobInterface $job): void
    {
        try {
            $listKey = $this->resolveListKeyForType(self::LIST_KEY_JOB, $job->getType());
            $this->redis->rPush($listKey, serialize($job));
        } catch (Throwable $exception) {
            $message = sprintf('Could not add job to Redis list. Error was: %s', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message);
        }
    }

    /**
     * @inheritDoc
     * @throws StoreException
     */
    public function dequeue(string $type): ?JobInterface
    {
        try {
            $listKey = $this->resolveListKeyForType(self::LIST_KEY_JOB, $type);
            if (!is_string($serializedJob = $this->redis->lPop($listKey))) {
                return null;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Could not pop job from Redis list. Error was: %s', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message);
        }

        /** @var JobInterface|false $job */
        $job = unserialize($serializedJob);

        if ($job instanceof JobInterface) {
            return $job;
        }

        $message = sprintf(
            'Could not deserialize job entry which was available in Redis. Entry was %s.',
            $serializedJob
        );
        $this->logger->error($message);
        throw new StoreException($message);
    }

    /**
     * @inheritDoc
     * @throws StoreException
     */
    public function markFailedJob(JobInterface $job): void
    {
        try {
            $listKey = $this->resolveListKeyForType(self::LIST_KEY_JOB_FAILED, $job->getType());
            $this->redis->rPush($listKey, serialize($job));
        } catch (Throwable $exception) {
            $message = sprintf('Could not mark job as failed. Error was: %s', $exception->getMessage());
            $this->logger->error($message);
            throw new StoreException($message);
        }
    }

    /**
     * @throws StoreException
     * @codeCoverageIgnore
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

    public function needsSetup(): bool
    {
        return false;
    }

    public function runSetup(): void
    {
        // No need for setup.
    }

    /**
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function getConnectionParameters(): array
    {
        $connectionParameters = $this->moduleConfiguration->getConnectionParameters($this->connectionKey);

        if (!isset($connectionParameters['host'])) {
            $message = 'PhpRedis class Redis expects at least host option to be set, none given.';
            $this->logger->error($message);
            throw new InvalidConfigurationException($message);
        }

        return $connectionParameters;
    }

    /**
     * @param string $list For example, job, job_failed...
     * @param string $jobType For example, FQ class name of the job instance
     * @return string Key with hashed type to conserve chars.
     */
    protected function resolveListKeyForType(string $list, string $jobType): string
    {
        return $list . ':' . sha1($jobType);
    }
}
