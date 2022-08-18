<?php

namespace SimpleSAML\Module\accounting\Stores\Builders;

use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

class JobsStoreBuilder extends AbstractStoreBuilder
{
    public function build(string $class): JobsStoreInterface
    {
        $store = parent::build($class);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (! $store instanceof JobsStoreInterface) {
            throw new StoreException(\sprintf('Class %s does not implement JobsStoreInterface.', $class));
        }

        return $store;
    }
}
