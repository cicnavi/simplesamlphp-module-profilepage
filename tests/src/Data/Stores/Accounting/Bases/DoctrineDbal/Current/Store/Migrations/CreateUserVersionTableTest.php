<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 */
class CreateUserVersionTableTest extends TestCase
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
        $this->tableName = 'vds_user_version';

        $this->connectionStub = $this->createStub(Connection::class);
        $this->dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $this->schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     * @throws Exception
     */
    public function testCanRunMigration(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
        $migration = new Migrations\CreateUserVersionTable($this->connection);
        $migration->run();
        $this->assertTrue($this->schemaManager->tablesExist($this->tableName));
        $migration->revert();
        $this->assertFalse($this->schemaManager->tablesExist($this->tableName));
    }
}
