<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Interfaces;

use SimpleSAML\Module\profilepage\Entities\ConnectedService;

interface ConnectedServicesInterface extends DataStoreInterface
{
    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag;
}
