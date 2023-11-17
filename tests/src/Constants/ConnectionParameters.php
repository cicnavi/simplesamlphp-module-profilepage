<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Constants;

class ConnectionParameters
{
    final public const DBAL_SQLITE_MEMORY = ['driver' => 'pdo_sqlite', 'memory' => true,];
}
