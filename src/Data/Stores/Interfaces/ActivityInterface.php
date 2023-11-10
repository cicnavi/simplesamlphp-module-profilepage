<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Interfaces;

use SimpleSAML\Module\profilepage\Entities\Activity;

interface ActivityInterface extends DataStoreInterface
{
    public function getActivity(string $userIdentifier, int $maxResults = null, int $firstResult = 0): Activity\Bag;
}
