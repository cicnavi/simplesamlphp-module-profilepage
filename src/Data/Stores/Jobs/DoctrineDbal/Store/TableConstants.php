<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;

class TableConstants
{
    final public const TABLE_NAME_JOB = 'job';
    final public const TABLE_NAME_JOB_FAILED = 'job_failed';

    // Both tables have same columns.
    final public const COLUMN_NAME_ID = 'id';
    final public const COLUMN_NAME_PAYLOAD = 'payload';
    final public const COLUMN_NAME_TYPE = 'type';
    final public const COLUMN_NAME_CREATED_AT = 'created_at';

    final public const COLUMN_TYPE_LENGTH = 1024;
}
