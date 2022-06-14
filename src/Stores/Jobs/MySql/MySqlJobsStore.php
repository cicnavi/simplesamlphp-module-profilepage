<?php

namespace SimpleSAML\Module\accounting\Stores\Jobs;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Factories\PdoConnectionFactory;
use SimpleSAML\Module\accounting\Stores\Connections\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

class MySqlJobsStore implements JobsStoreInterface
{
    protected PdoConnection $pdoConnection;

    public function __construct(ModuleConfiguration $moduleConfiguration, PdoConnectionFactory $pdoConnectionFactory)
    {
        $this->pdoConnection = $pdoConnectionFactory
            ->build($moduleConfiguration->getStoreConnection(self::class));
    }

    public function needsSetUp(): bool
    {
        return true;
    }
}
