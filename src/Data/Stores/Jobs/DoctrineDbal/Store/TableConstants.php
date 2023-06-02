<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;

class TableConstants
{
    public const TABLE_NAME_JOB = 'job';
    public const TABLE_NAME_JOB_FAILED = 'job_failed';

    // Both tables have same columns.
    public const COLUMN_NAME_ID = 'id';
    public const COLUMN_NAME_PAYLOAD = 'payload';
    public const COLUMN_NAME_TYPE = 'type';
    public const COLUMN_NAME_CREATED_AT = 'created_at';

    public const COLUMN_TYPE_LENGTH = 1024;
}
