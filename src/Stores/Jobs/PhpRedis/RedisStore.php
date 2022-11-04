<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis;

use Psr\Log\LoggerInterface;
use Redis;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Bases\AbstractStore;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

class RedisStore extends AbstractStore implements JobsStoreInterface
{
    protected Redis $redis;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        Redis $redis = null
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionKey, $connectionType);

        $this->redis = $redis ?? new Redis($this->moduleConfiguration->getConnectionParameters($this->connectionKey));
    }

    /**
     * @inheritDoc
     */
    public function enqueue(JobInterface $job): void
    {
        // TODO: Implement enqueue() method.
    }

    /**
     * @inheritDoc
     */
    public function dequeue(string $type = null): ?JobInterface
    {
        // TODO: Implement dequeue() method.
        return null;
    }

    /**
     * @inheritDoc
     */
    public function markFailedJob(JobInterface $job): void
    {
        // TODO: Implement markFailedJob() method.
    }

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
}
