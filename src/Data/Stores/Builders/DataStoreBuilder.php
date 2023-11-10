<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Builders;

use SimpleSAML\Module\profilepage\Data\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\profilepage\Data\Stores\Interfaces\DataStoreInterface;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration\ConnectionType;

class DataStoreBuilder extends AbstractStoreBuilder
{
    /**
     * @throws StoreException
     */
    public function build(
        string $class,
        string $connectionKey = null,
        string $connectionType = ConnectionType::MASTER
    ): DataStoreInterface {
        if (!is_subclass_of($class, DataStoreInterface::class)) {
            throw new StoreException(
                sprintf('Class \'%s\' does not implement interface \'%s\'.', $class, DataStoreInterface::class)
            );
        }

        /** @var DataStoreInterface $store */
        $store = $this->buildGeneric($class, [$connectionKey, $connectionType]);

        return $store;
    }
}
