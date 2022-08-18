<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;

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
}
