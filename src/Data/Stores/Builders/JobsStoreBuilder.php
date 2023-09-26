<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Builders;

use SimpleSAML\Module\accounting\Data\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration\ConnectionType;

use function sprintf;

class JobsStoreBuilder extends AbstractStoreBuilder
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

        $connectionKey ??= $this->moduleConfiguration->getClassConnectionKey($class);

        /** @var JobsStoreInterface $store */
        $store = $this->buildGeneric($class, [$connectionKey, $connectionType]);

        return $store;
    }
}
