<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Builders;

use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

use function sprintf;

class JobsStoreBuilder extends AbstractStoreBuilder
{
    /**
     * @throws StoreException
     */
    public function build(): JobsStoreInterface
    {
        $jobsStoreClass = $this->moduleConfiguration->getJobsStoreClass();

        $store = $this->buildGeneric($jobsStoreClass);

        if (!is_subclass_of($store, JobsStoreInterface::class)) {
            throw new StoreException(sprintf('Class %s does not implement JobsStoreInterface.', $jobsStoreClass));
        }

        return $store;
    }
}
