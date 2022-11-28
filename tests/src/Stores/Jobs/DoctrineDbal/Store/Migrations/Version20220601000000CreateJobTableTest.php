<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 */
class Version20220601000000CreateJobTableTest extends TestCase
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;
    protected string $tableName;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOB);
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     * @throws Exception
     */
    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
        $migration = new Version20220601000000CreateJobTable($this->connection);
        $migration->run();
        $this->assertTrue($this->schemaManager->tablesExist($this->tableName));
        $migration->revert();
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
    }

    /**
     * @throws StoreException
     */
    public function testRunThrowsMigrationException(): void
    {
        $connectionStub = $this->createStub(Connection::class);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);

        $connectionStub->method('dbal')->willReturn($dbalStub);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $schemaManagerStub->method('createTable')
            ->willThrowException(new Exception('test'));

        $migration = new Version20220601000000CreateJobTable($connectionStub);
        $this->expectException(MigrationException::class);
        $migration->run();
    }

    /**
     * @throws StoreException
     */
    public function testRevertThrowsMigrationException(): void
    {
        $connectionStub = $this->createStub(Connection::class);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);

        $connectionStub->method('dbal')->willReturn($dbalStub);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $schemaManagerStub->method('dropTable')
            ->willThrowException(new Exception('test'));

        $migration = new Version20220601000000CreateJobTable($connectionStub);
        $this->expectException(MigrationException::class);
        $migration->revert();
    }
}
