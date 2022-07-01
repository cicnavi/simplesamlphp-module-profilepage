<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;

abstract class AbstractMigration implements MigrationInterface
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
    }
}
