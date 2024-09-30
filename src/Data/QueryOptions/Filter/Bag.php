<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\QueryOptions\Filter;

use SimpleSAML\Module\profilepage\Data\QueryOptions\Filter;
use SimpleSAML\Module\profilepage\Data\Stores\StoreType;

class Bag
{
    /**
     * @var Filter[]
     */
    protected array $filters = [];

    public function __construct(Filter ...$filters)
    {
        $this->filters = $filters;
    }

    public function add(Filter $filter): void
    {
        $this->filters[] = $filter;
    }

    public function all(): array
    {
        return $this->filters;
    }

    /**
     * @param string[]|null $names
     * @param Operation[]|null $operations
     * @return Filter[]
     */
    public function for(array $names = null, array $operations = null): array
    {
        $filters = $this->all();

        if ($names) {
            $filters = array_filter($filters, fn(Filter $filter): bool => in_array($filter->name, $names));
        }

        if ($operations) {
            $filters = array_filter($filters, fn(Filter $filter): bool => in_array($filter->operation, $operations));
        }

        return $filters;
    }
}
