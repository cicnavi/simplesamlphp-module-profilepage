<?php

namespace SimpleSAML\Module\accounting\Stores\Builders;

use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

class JobsStoreBuilder extends AbstractStoreBuilder
{
    /**
     * @throws StoreException
     */
    public function build(): JobsStoreInterface
    {
        $jobsStoreClass = $this->moduleConfiguration->getJobsStore();

        $store = $this->buildGenericStore($jobsStoreClass);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (! $store instanceof JobsStoreInterface) {
            throw new StoreException(\sprintf('Class %s does not implement JobsStoreInterface.', $jobsStoreClass));
        }

        return $store;
    }
}
