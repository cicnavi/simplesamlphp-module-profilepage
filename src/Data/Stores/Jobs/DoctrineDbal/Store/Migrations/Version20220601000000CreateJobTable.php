<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations;

use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;

class Version20220601000000CreateJobTable extends Store\Migrations\Bases\AbstractCreateJobsTable
{
    protected function getJobsTableName(): string
    {
        return 'job';
    }
}
