<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection *
 */
class Version20220801000200CreateSpTableTest extends TestCase
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;
    protected string $tableName;
    protected Stub $connectionStub;
    protected Stub $dbalStub;
    protected Stub $schemaManagerStub;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = 'vds_sp';

        $this->connectionStub = $this->createStub(Connection::class);
        $this->dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $this->schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
        $migration = new Migrations\Version20220801000200CreateSpTable($this->connection);
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
        $this->connectionStub->method('preparePrefixedTableName')->willReturn($this->tableName);
        $this->schemaManagerStub->method('createTable')
            ->willThrowException(new Exception('test'));
        $this->dbalStub->method('createSchemaManager')->willReturn($this->schemaManagerStub);
        $this->connectionStub->method('dbal')->willReturn($this->dbalStub);

        $migration = new Migrations\Version20220801000200CreateSpTable($this->connectionStub);
        $this->expectException(MigrationException::class);
        $migration->run();
    }

    /**
     * @throws StoreException
     */
    public function testRevertThrowsMigrationException(): void
    {
        $this->schemaManagerStub->method('dropTable')
            ->willThrowException(new Exception('test'));
        $this->dbalStub->method('createSchemaManager')->willReturn($this->schemaManagerStub);
        $this->connectionStub->method('dbal')->willReturn($this->dbalStub);

        $migration = new Migrations\Version20220801000200CreateSpTable($this->connectionStub);
        $this->expectException(MigrationException::class);
        $migration->revert();
    }

    /**
     * @throws StoreException
     */
    public function testRunThrowsOnIvalidTableNameIdp(): void
    {
        $this->connectionStub->method('preparePrefixedTableName')
            ->willReturnOnConsecutiveCalls(''); // Invalid (empty) name for table

        $migration = new Migrations\Version20220801000200CreateSpTable($this->connectionStub);
        $this->expectException(MigrationException::class);
        $migration->run();
    }
}
