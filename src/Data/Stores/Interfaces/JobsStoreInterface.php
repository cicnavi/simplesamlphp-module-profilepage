<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface JobsStoreInterface extends StoreInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self;

    /**
     * Add job to queue
     */
    public function enqueue(JobInterface $job): void;

    /**
     * Get job from queue
     * @param string $type Type of the job, typically FQ class name of job object.
     * @return ?JobInterface
     */
    public function dequeue(string $type): ?JobInterface;

    public function markFailedJob(JobInterface $job): void;
}
