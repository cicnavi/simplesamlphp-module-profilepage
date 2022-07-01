<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations;

use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000100CreateFailedJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 *
 */
class Version20220601000100CreateFailedJobsTableTest extends TestCase
{
    protected Connection $connection;
    protected \Doctrine\DBAL\Schema\AbstractSchemaManager $schemaManager;
    protected string $tableName;

    protected function setUp(): void
    {
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_FAILED_JOBS);
    }

    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
        $migration = new Migrations\Version20220601000100CreateFailedJobsTable($this->connection);
        $migration->run();
        $this->assertTrue($this->schemaManager->tablesExist($this->tableName));
        $migration->revert();
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
    }
}
