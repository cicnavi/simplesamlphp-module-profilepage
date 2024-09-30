<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data;

use SimpleSAML\Module\profilepage\Data\QueryOptions\Filter\Bag;

class QueryOptions
{
    public function __construct(
        public readonly ?int $maxResults = null,
        public readonly int $firstResult = 0,
        public readonly Bag $filterBag = new Bag()
    ) {
    }
}
