<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Interfaces;

use SimpleSAML\Module\accounting\Entities\ConnectedService;

interface ConnectedServicesInterface extends DataStoreInterface
{
    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag;
}
