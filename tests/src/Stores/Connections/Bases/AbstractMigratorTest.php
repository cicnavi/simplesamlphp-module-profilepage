<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\Bases;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 */
class AbstractMigratorTest extends TestCase
{
    protected Connection $connection;
    protected AbstractSchemaManager $schemaManager;
    protected string $tableName;
    protected MockObject $loggerServiceMock;
    protected ModuleConfiguration $moduleConfiguration;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->schemaManager = $this->connection->dbal()->createSchemaManager();
        $this->tableName = $this->connection->preparePrefixedTableName(Migrator::TABLE_NAME);

        $this->loggerServiceMock = $this->createMock(Logger::class);

        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
    }

    /**
     * @throws StoreException
     */
    public function testCanGatherMigrationClassesFromDirectory(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $directory = $this->getSampleMigrationsDirectory();

        $namespace = $this->getSampleNameSpace();

        $migrationClasses = $migrator->gatherMigrationClassesFromDirectory($directory, $namespace);

        $this->assertTrue(in_array($namespace . '\Version20220601000000CreateJobTable', $migrationClasses));
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     * @throws MigrationException
     */
    public function testCanRunMigrationClasses(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $directory = $this->getSampleMigrationsDirectory();

        $namespace = $this->getSampleNameSpace();

        $migrationClasses = $migrator->gatherMigrationClassesFromDirectory($directory, $namespace);

        $jobsTableName = $this->connection->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOB);

        $this->assertFalse($this->schemaManager->tablesExist($jobsTableName));

        $migrator->runMigrationClasses($migrationClasses);

        $this->assertTrue($this->schemaManager->tablesExist($jobsTableName));
    }

    /**
     * @throws StoreException
     */
    public function testCanGatherOnlyMigrationClasses(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $directory = __DIR__;
        $namespace = __NAMESPACE__;

        $this->assertEmpty($migrator->gatherMigrationClassesFromDirectory($directory, $namespace));
    }

    /**
     * @throws StoreException
     */
    public function testMigrationExceptionHaltsExecution(): void
    {
        $migration = new class ($this->connection) extends AbstractMigration
        {
            public function run(): void
            {
                throw new Exception('Something went wrong.');
            }

            public function revert(): void
            {
            }
        };

        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $this->expectException(MigrationException::class);

        $migrator->runMigrationClasses([get_class($migration)]);
    }

    /**
     * @throws StoreException
     */
    public function testCanGetNonImplementedMigrationClasses(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $nonImplementedMigrationClasses = $migrator->getNonImplementedMigrationClasses(
            $this->getSampleMigrationsDirectory(),
            $this->getSampleNameSpace()
        );

        $this->assertTrue(in_array(
            Store\Migrations\Version20220601000000CreateJobTable::class,
            $nonImplementedMigrationClasses
        ));
    }

    /**
     * @throws StoreException
     */
    public function testCanFindOutIfNonImplementedMigrationClassesExist(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $this->assertTrue($migrator->hasNonImplementedMigrationClasses(
            $this->getSampleMigrationsDirectory(),
            $this->getSampleNameSpace()
        ));
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanRunNonImplementedMigrationClasses(): void
    {
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $directory = $this->getSampleMigrationsDirectory();
        $namespace = $this->getSampleNameSpace();

        $this->assertTrue($migrator->hasNonImplementedMigrationClasses($directory, $namespace));

        $migrator->runNonImplementedMigrationClasses($directory, $namespace);

        $this->assertFalse($migrator->hasNonImplementedMigrationClasses($directory, $namespace));
    }

    protected function getSampleMigrationsDirectory(): string
    {
        return $this->moduleConfiguration->getModuleSourceDirectory() . DIRECTORY_SEPARATOR .
            'Stores' . DIRECTORY_SEPARATOR . 'Jobs' . DIRECTORY_SEPARATOR . 'DoctrineDbal' . DIRECTORY_SEPARATOR .
            'Store' . DIRECTORY_SEPARATOR . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    protected function getSampleNameSpace(): string
    {
        return Store::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }
}
