<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Constants;

class ConnectionParameters
{
    public const DBAL_SQLITE_MEMORY = ['driver' => 'pdo_sqlite', 'memory' => true,];
}