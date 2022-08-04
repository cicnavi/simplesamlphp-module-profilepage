<?php

namespace SimpleSAML\Module\accounting\Stores\Jobs\Interfaces;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;

interface JobsStoreInterface
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
