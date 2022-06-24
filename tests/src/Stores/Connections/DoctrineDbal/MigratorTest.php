<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 */
class MigratorTest extends TestCase
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;
    protected string $tableName;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(Migrator::TABLE_NAME);

        $this->loggerServiceMock = $this->createMock(LoggerService::class);
    }

    public function testMigratorCanCreateMigrationsTable(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist([$this->tableName]));

        /** @psalm-suppress InvalidArgument Using mock instead of LoggerService instance */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $this->assertTrue($migrator->needsSetup());

        $migrator->runSetup();

        $this->assertFalse($migrator->needsSetup());
        $this->assertTrue($this->schemaManager->tablesExist([$this->tableName]));
    }

    public function testRunningMigratiorSetupMultipleTimesLogsWarning(): void
    {
        $this->loggerServiceMock
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('setup is not needed'));

        /** @psalm-suppress InvalidArgument Using mock instead of LoggerService instance */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $this->assertTrue($migrator->needsSetup());

        $migrator->runSetup();
        $migrator->runSetup();
    }
}
