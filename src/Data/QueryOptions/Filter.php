<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\QueryOptions;

use SimpleSAML\Module\profilepage\Data\QueryOptions\Filter\Operation;
use SimpleSAML\Module\profilepage\Data\Stores\StoreType;

class Filter
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $rawValue,
        public readonly Operation $operation
    ) {
    }

    public function value(StoreType $storeType): string
    {
        return $this->operation->formatValue($storeType, $this->rawValue);
    }

    public function operator(StoreType $storeType): string
    {
        return $this->operation->getOperator($storeType);
    }
}
