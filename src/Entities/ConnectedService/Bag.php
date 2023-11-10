<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\ConnectedService;

use SimpleSAML\Module\profilepage\Entities\ConnectedService;

class Bag
{
    /**
     * @var ConnectedService[]
     */
    protected array $connectedServiceProviders = [];

    public function addOrReplace(ConnectedService $connectedService): void
    {
        $spEntityId = $connectedService->getServiceProvider()->getEntityId();

        $this->connectedServiceProviders[$spEntityId] = $connectedService;
    }

    public function getAll(): array
    {
        return $this->connectedServiceProviders;
    }
}
