<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Migrations;

use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable;

class Version20220601000100CreateJobFailedTable extends AbstractCreateJobsTable
{
    protected function getJobsTableName(): string
    {
        return 'job_failed';
    }
}
