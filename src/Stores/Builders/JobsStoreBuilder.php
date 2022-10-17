<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Builders;

use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration\ConnectionType;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

use function sprintf;

class JobsStoreBuilder extends Bases\AbstractStoreBuilder
{
    /**
     * @throws StoreException
     */
    public function build(
        string $class,
        string $connectionKey = null,
        string $connectionType = ConnectionType::MASTER
    ): JobsStoreInterface {
        if (!is_subclass_of($class, JobsStoreInterface::class)) {
            throw new StoreException(
                sprintf('Class \'%s\' does not implement interface \'%s\'.', $class, JobsStoreInterface::class)
            );
        }

        $connectionKey = $connectionKey ?? $this->moduleConfiguration->getClassConnectionKey($class);

        /** @var JobsStoreInterface $store */
        $store = $this->buildGeneric($class, [$connectionKey, $connectionType]);

        return $store;
    }
}
