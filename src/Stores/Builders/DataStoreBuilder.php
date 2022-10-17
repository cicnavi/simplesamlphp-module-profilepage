<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Builders;

use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration\ConnectionType;
use SimpleSAML\Module\accounting\Stores\Interfaces\DataStoreInterface;

class DataStoreBuilder extends Bases\AbstractStoreBuilder
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
