<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface JobsStoreInterface extends StoreInterface
{
    /**
     * Add job to queue
     * @param JobInterface $job
     * @return void
     */
    public function enqueue(JobInterface $job): void;

    /**
     * Get job from queue
     * @param string|null $type
     * @return ?JobInterface
     */
    public function dequeue(string $type = null): ?JobInterface;

    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self;
}
