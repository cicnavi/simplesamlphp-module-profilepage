<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Data\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\profilepage\Exceptions\InvalidValueException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Logger;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

use function PHPUnit\Framework\assertFalse;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class MigratorTest extends TestCase
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;
    protected string $tableName;

    protected MockObject $loggerServiceMock;
    protected ModuleConfiguration $moduleConfiguration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(Migrator::TABLE_NAME);

        $this->loggerServiceMock = $this->createMock(Logger::class);

        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_profilepage.php');
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanCreateMigrationsTable(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist([$this->tableName]));

        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $this->assertTrue($migrator->needsSetup());

        $migrator->runSetup();

        $this->assertFalse($migrator->needsSetup());
        $this->assertTrue($this->schemaManager->tablesExist([$this->tableName]));
    }

    /**
     * @throws StoreException
     */
    public function testRunningMigratorSetupMultipleTimesLogsWarning(): void
    {
        $this->loggerServiceMock
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('setup is not needed'));

        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $this->assertTrue($migrator->needsSetup());

        $migrator->runSetup();
        $migrator->runSetup();
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testCanRunMigrationClasses(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $tableNameJobs = $this->connection->preparePrefixedTableName(
            TableConstants::TABLE_NAME_JOB
        );
        $this > assertFalse($this->schemaManager->tablesExist($tableNameJobs));

        $migrator->runMigrationClasses([Store\Migrations\Version20220601000000CreateJobTable::class]);

        $this->assertTrue($this->schemaManager->tablesExist($tableNameJobs));
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanOnlyRunDoctrineDbalMigrationClasses(): void
    {
        $migration = new class implements MigrationInterface {
            public function run(): void
            {
            }
            public function revert(): void
            {
            }
        };

        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $this->expectException(InvalidValueException::class);

        $migrator->runMigrationClasses([$migration::class]);
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanGetImplementedMigrationClasses(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $this->assertEmpty($migrator->getImplementedMigrationClasses());

        $migrator->runNonImplementedMigrationClasses(
            $this->getSampleMigrationsDirectory(),
            $this->getSampleNameSpace()
        );

        $this->assertNotEmpty($migrator->getImplementedMigrationClasses());
    }

    public function testThrowsStoreExceptionOnInitialization(): void
    {
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createSchemaManager')->willThrowException(new Exception('test'));
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $this->expectException(StoreException::class);

        (new Migrator($connectionStub, $this->loggerServiceMock));
    }

    /**
     * @throws StoreException
     */
    public function testThrowsStoreExceptionOnNeedsSetup(): void
    {
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
        $schemaManagerStub->method('tablesExist')
            ->willThrowException(new Exception('test'));
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $migrator = new Migrator($connectionStub, $this->loggerServiceMock);

        $this->expectException(StoreException::class);

        $migrator->needsSetup();
    }

    /**
     * @throws StoreException
     */
    public function testThrowsStoreExceptionOnCreateMigrationsTable(): void
    {
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
        $schemaManagerStub->method('tablesExist')
            ->willReturn(false);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $connectionStub = $this->createStub(
            Connection::class
        );
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $migrator = new Migrator($connectionStub, $this->loggerServiceMock);

        $this->expectException(StoreException::class);

        $migrator->runSetup();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testThrowsStoreExceptionOnMarkingImplementedClass(): void
    {
        $queryBuilderStub = $this->createStub(QueryBuilder::class);
        $queryBuilderStub->method('insert')
            ->willThrowException(new Exception('test'));
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createQueryBuilder')->willReturn($queryBuilderStub);
        $connectionStub = $this->createStub(
            Connection::class
        );
        $connectionStub->method('dbal')->willReturn($dbalStub);
        $connectionStub->method('preparePrefixedTableName')->willReturn(Migrator::TABLE_NAME);

        $migrator = new Migrator($connectionStub, $this->loggerServiceMock);
        $migrator->runSetup();

        $this->expectException(StoreException::class);

        $migrator->runMigrationClasses([Store\Migrations\Version20220601000000CreateJobTable::class]);
    }

    public function testThrowsStoreExceptionOnGetImplementedMigrationClasses(): void
    {
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createQueryBuilder')->willThrowException(new Exception('test'));
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $this->expectException(StoreException::class);

        (new Migrator($connectionStub, $this->loggerServiceMock))->getImplementedMigrationClasses();
    }

    protected function getSampleMigrationsDirectory(): string
    {
        return $this->moduleConfiguration->getModuleSourceDirectory() . DIRECTORY_SEPARATOR .
            'Data' . DIRECTORY_SEPARATOR .
            'Stores' . DIRECTORY_SEPARATOR .
            'Jobs' . DIRECTORY_SEPARATOR .
            'DoctrineDbal' . DIRECTORY_SEPARATOR .
            'Store' . DIRECTORY_SEPARATOR .
            AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    protected function getSampleNameSpace(): string
    {
        return Store::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }
}
