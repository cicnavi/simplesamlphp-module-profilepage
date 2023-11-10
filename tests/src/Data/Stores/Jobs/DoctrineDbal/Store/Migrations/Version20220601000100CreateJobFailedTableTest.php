<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 */
class Version20220601000100CreateJobFailedTableTest extends TestCase
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
        $this->tableName = $this->connection->preparePrefixedTableName(
            TableConstants::TABLE_NAME_JOB_FAILED
        );
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     * @throws Exception
     */
    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
        $migration = new Migrations\Version20220601000100CreateJobFailedTable($this->connection);
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

        $migration = new Migrations\Version20220601000100CreateJobFailedTable($connectionStub);
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

        $migration = new Migrations\Version20220601000100CreateJobFailedTable($connectionStub);
        $this->expectException(MigrationException::class);
        $migration->revert();
    }
}
