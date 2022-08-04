<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;

interface JobsStoreInterface extends StoreInterface
{
    /**
     * Add job to queue
     * @param AbstractJob $job
     * @return void
     */
    public function enqueue(AbstractJob $job): void;

    /**
     * Get job from queue
     * @return AbstractJob
     */
    public function dequeue(): AbstractJob;
}
