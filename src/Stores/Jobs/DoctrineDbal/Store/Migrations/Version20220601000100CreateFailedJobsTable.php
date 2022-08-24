<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations;

use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

class Version20220601000100CreateFailedJobsTable extends Store\Migrations\Bases\AbstractCreateJobsTable
{
    protected function getJobsTableName(): string
    {
        return 'failed_jobs';
    }
}
