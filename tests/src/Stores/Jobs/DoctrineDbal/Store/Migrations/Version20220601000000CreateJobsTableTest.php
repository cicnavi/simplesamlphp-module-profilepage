<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobsTable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobsTable
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 */
class Version20220601000000CreateJobsTableTest extends TestCase
{
    protected Connection $connection;
    protected \Doctrine\DBAL\Schema\AbstractSchemaManager $schemaManager;
    protected string $tableName;

    protected function setUp(): void
    {
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOBS);
    }

    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
        $migration = new Version20220601000000CreateJobsTable($this->connection);
        $migration->run();
        $this->assertTrue($this->schemaManager->tablesExist($this->tableName));
        $migration->revert();
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
    }

    public function testRunThrowsMigrationException(): void
    {
        $connectionStub = $this->createStub(Connection::class);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);

        $connectionStub->method('dbal')->willReturn($dbalStub);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $schemaManagerStub->method('createTable')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));

        $migration = new Version20220601000000CreateJobsTable($connectionStub);
        $this->expectException(MigrationException::class);
        $migration->run();
    }

    public function testRevertThrowsMigrationException(): void
    {
        $connectionStub = $this->createStub(Connection::class);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);

        $connectionStub->method('dbal')->willReturn($dbalStub);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $schemaManagerStub->method('dropTable')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));

        $migration = new Version20220601000000CreateJobsTable($connectionStub);
        $this->expectException(MigrationException::class);
        $migration->revert();
    }
}
