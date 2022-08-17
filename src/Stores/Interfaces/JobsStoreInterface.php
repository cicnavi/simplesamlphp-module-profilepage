<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;

interface JobsStoreInterface extends StoreInterface
{
    /**
     * Add job to queue
     * @param JobInterface $job
     * @param string|null $type Optional job type designation. If not null, payload class FQN will be used.
     * @return void
     */
    public function enqueue(JobInterface $job, string $type = null): void;

    /**
     * Get job from queue
     * @param string|null $type
     * @return ?JobInterface
     */
    public function dequeue(string $type = null): ?JobInterface;
}
