<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Interfaces;

use SimpleSAML\Module\accounting\Entities\Activity;

interface ActivityInterface extends DataStoreInterface
{
    public function getActivity(string $userIdentifier, int $maxResults, int $firstResult): Activity\Bag;
}