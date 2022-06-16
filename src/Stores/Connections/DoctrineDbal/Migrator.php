<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

class Migrator
{
    public const TABLE_NAME = 'migrations';

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}
