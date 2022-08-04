<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\Exceptions\InvalidValueException;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;

use function PHPUnit\Framework\assertFalse;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000000CreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000100CreateFailedJobsTable
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
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
    protected ModuleConfiguration $moduleConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);
        // TODO mivanci ostavi samo sqlite verziju
//        $this->connection = new Connection([
//            'dbname' => 'accounting',
//           'user' => 'apps',
//           'password' => 'apps',
//           'host' => '127.0.0.1',
//           'port' => '33306',
//           'driver' => 'pdo_mysql',
//                                               ]);

        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(Migrator::TABLE_NAME);

        $this->loggerServiceMock = $this->createMock(LoggerService::class);

        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
    }

    public function testCanCreateMigrationsTable(): void
    {
        $this->assertFalse($this->schemaManager->tablesExist([$this->tableName]));

        /** @psalm-suppress InvalidArgument Using mock instead of LoggerService instance */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $this->assertTrue($migrator->needsSetup());

        $migrator->runSetup();

        $this->assertFalse($migrator->needsSetup());
        $this->assertTrue($this->schemaManager->tablesExist([$this->tableName]));
    }

    public function testRunningMigratorSetupMultipleTimesLogsWarning(): void
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

    public function testCanRunMigrationClasses(): void
    {
        /** @psalm-suppress InvalidArgument */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $tableNameJobs = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_JOBS);
        $this > assertFalse($this->schemaManager->tablesExist($tableNameJobs));

        $migrator->runMigrationClasses([JobsStore\Migrations\Version20220601000000CreateJobsTable::class]);

        $this->assertTrue($this->schemaManager->tablesExist($tableNameJobs));
    }

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

        /** @psalm-suppress InvalidArgument */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $this->expectException(InvalidValueException::class);

        $migrator->runMigrationClasses([get_class($migration)]);
    }

    public function testCanGetImplementedMigrationClasses(): void
    {
        /** @psalm-suppress InvalidArgument */
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
        $dbalStub->method('createSchemaManager')->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        (new Migrator($connectionStub, $this->loggerServiceMock));
    }

    public function testThrowsStoreExceptionOnNeedsSetup(): void
    {
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
        $schemaManagerStub->method('tablesExist')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        /** @psalm-suppress InvalidArgument */
        $migrator = new Migrator($connectionStub, $this->loggerServiceMock);

        $this->expectException(StoreException::class);

        $migrator->needsSetup();
    }

    public function testThrowsStoreExceptionOnCreateMigrationsTable(): void
    {
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
        $schemaManagerStub->method('tablesExist')
            ->willReturn(false);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        /** @psalm-suppress InvalidArgument */
        $migrator = new Migrator($connectionStub, $this->loggerServiceMock);

        $this->expectException(StoreException::class);

        $migrator->runSetup();
    }

    public function testThrowsStoreExceptionOnMarkingImplementedClass(): void
    {
        $queryBuilderStub = $this->createStub(QueryBuilder::class);
        $queryBuilderStub->method('insert')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createQueryBuilder')->willReturn($queryBuilderStub);
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);
        $connectionStub->method('preparePrefixedTableName')->willReturn(Migrator::TABLE_NAME);

        /** @psalm-suppress InvalidArgument */
        $migrator = new Migrator($connectionStub, $this->loggerServiceMock);
        $migrator->runSetup();

        $this->expectException(StoreException::class);

        $migrator->runMigrationClasses([JobsStore\Migrations\Version20220601000000CreateJobsTable::class]);
    }

    public function testThrowsStoreExceptionOnGetImplementedMigrationClasses(): void
    {
        $schemaManagerStub = $this->createStub(AbstractSchemaManager::class);
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createQueryBuilder')->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $dbalStub->method('createSchemaManager')->willReturn($schemaManagerStub);
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        (new Migrator($connectionStub, $this->loggerServiceMock))->getImplementedMigrationClasses();
    }

    protected function getSampleMigrationsDirectory(): string
    {
        return $this->moduleConfiguration->getModuleSourceDirectory() . DIRECTORY_SEPARATOR .
            'Stores' . DIRECTORY_SEPARATOR . 'Jobs' . DIRECTORY_SEPARATOR . 'DoctrineDbal' . DIRECTORY_SEPARATOR .
            'JobsStore' . DIRECTORY_SEPARATOR . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    protected function getSampleNameSpace(): string
    {
        return JobsStore::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }
}
