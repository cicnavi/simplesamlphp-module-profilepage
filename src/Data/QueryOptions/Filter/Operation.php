<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\QueryOptions\Filter;

use SimpleSAML\Module\profilepage\Data\Stores\StoreType;

enum Operation
{
    case Like;
    case Equals;

    public function getOperator(StoreType $storeType): string
    {
        return match ($storeType) {
            StoreType::DoctrineDbal => match ($this) {
                self::Like => 'like',
                self::Equals => '=',
            }
        };
    }

    public function formatValue(StoreType $storeType, mixed $value): string
    {
        return match ($storeType) {
            StoreType::DoctrineDbal => match ($this) {
                self::Like => '%' . $value . '%',
                self::Equals => (string)$value,
            }
        };
    }
}
