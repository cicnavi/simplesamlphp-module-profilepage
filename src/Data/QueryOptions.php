<?php

namespace SimpleSAML\Module\profilepage\Data;

use SimpleSAML\Module\profilepage\Data\QueryOptions\Filters\Bag;

class QueryOptions
{
    protected Bag $filtersBag;

    public function __construct(
        protected ?int $maxResults = null,
        protected int $firstResult = 0,
        Bag $filtersBag = null
    ) {
        $this->filtersBag = $filtersBag ?? new Bag();
    }

    public function getFiltersBag(): Bag
    {
        return $this->filtersBag;
    }
}
