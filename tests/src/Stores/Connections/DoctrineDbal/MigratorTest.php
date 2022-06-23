<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 */
class MigratorTest extends TestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);
    }

    public function testMigratorCanCreateMigrationsTable(): void
    {
        $schemaManager = $this->connection->dbal()->createSchemaManager();

        $this->assertFalse($schemaManager->tablesExist(Migrator::TABLE_NAME));

        $migrator = new Migrator($this->connection);

        $this->assertTrue($migrator->needsSetUp());

        $migrator->runSetUp();

        $this->assertFalse($migrator->needsSetUp());

        $this->assertTrue($schemaManager->tablesExist(Migrator::TABLE_NAME));
    }
}
