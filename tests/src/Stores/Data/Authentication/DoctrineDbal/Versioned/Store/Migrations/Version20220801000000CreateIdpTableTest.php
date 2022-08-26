<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 */
class Version20220801000000CreateIdpTableTest extends TestCase
{
    protected Connection $connection;
    protected \Doctrine\DBAL\Schema\AbstractSchemaManager $schemaManager;
    protected string $tableNameIdp;
    protected \PHPUnit\Framework\MockObject\Stub $connectionStub;
    protected \PHPUnit\Framework\MockObject\Stub $dbalStub;
    protected \PHPUnit\Framework\MockObject\Stub $schemaManagerStub;

    protected function setUp(): void
    {
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableNameIdp = 'vds_idp';

        $this->connectionStub = $this->createStub(Connection::class);
        $this->dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $this->schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
    }

    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableNameIdp));
        $migration = new Migrations\Version20220801000000CreateIdpTable($this->connection);
        $migration->run();
        $this->assertTrue($this->schemaManager->tablesExist($this->tableNameIdp));
        $migration->revert();
        $this->assertFalse($this->schemaManager->tablesExist($this->tableNameIdp));
    }

    public function testRunThrowsMigrationException(): void
    {
        $this->connectionStub->method('preparePrefixedTableName')->willReturn($this->tableNameIdp);
        $this->schemaManagerStub->method('createTable')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $this->dbalStub->method('createSchemaManager')->willReturn($this->schemaManagerStub);
        $this->connectionStub->method('dbal')->willReturn($this->dbalStub);

        /** @psalm-suppress InvalidArgument */
        $migration = new Migrations\Version20220801000000CreateIdpTable($this->connectionStub);
        $this->expectException(MigrationException::class);
        $migration->run();
    }

    public function testRevertThrowsMigrationException(): void
    {
        $this->schemaManagerStub->method('dropTable')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $this->dbalStub->method('createSchemaManager')->willReturn($this->schemaManagerStub);
        $this->connectionStub->method('dbal')->willReturn($this->dbalStub);

        /** @psalm-suppress InvalidArgument */
        $migration = new Migrations\Version20220801000000CreateIdpTable($this->connectionStub);
        $this->expectException(MigrationException::class);
        $migration->revert();
    }

    public function testRunThrowsOnIvalidTableNameIdp(): void
    {
        $this->connectionStub->method('preparePrefixedTableName')
            ->willReturnOnConsecutiveCalls(''); // Invalid (empty) name for table

        /** @psalm-suppress InvalidArgument */
        $migration = new Migrations\Version20220801000000CreateIdpTable($this->connectionStub);
        $this->expectException(MigrationException::class);
        $migration->run();
    }

    // TODO mivanci nastavi s ostalim tablicama.
}
