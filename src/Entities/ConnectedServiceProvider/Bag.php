<?php

namespace SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;

use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;

class Bag
{
    /**
     * @var ConnectedServiceProvider[]
     */
    protected array $connectedServiceProviders = [];

    public function addOrReplace(ConnectedServiceProvider $connectedServiceProvider): void
    {
        $spEntityId = $connectedServiceProvider->getServiceProvider()->getEntityId();

        $this->connectedServiceProviders[$spEntityId] = $connectedServiceProvider;
    }

    public function getAll(): array
    {
        return $this->connectedServiceProviders;
    }
}
