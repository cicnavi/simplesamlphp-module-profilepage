<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\Bases;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000000CreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 */
class AbstractMigratorTest extends TestCase
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

    public function testCanGatherMigrationClassesFromDirectory(): void
    {
        /** @psalm-suppress InvalidArgument Using mock instead of LoggerService instance */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $directory = $this->getSampleMigrationsDirectory();

        $namespace = $this->getSampleNameSpace();

        $migrationClasses = $migrator->gatherMigrationClassesFromDirectory($directory, $namespace);

        $this->assertTrue(in_array($namespace . '\Version20220601000000CreateJobsTable', $migrationClasses));
    }

    public function testCanRunMigrationClasses(): void
    {
        /** @psalm-suppress InvalidArgument Using mock instead of LoggerService instance */
        $migrator = new Migrator($this->connection, $this->loggerServiceMock);

        $migrator->runSetup();

        $directory = $this->getSampleMigrationsDirectory();

        $namespace = $this->getSampleNameSpace();

        $migrationClasses = $migrator->gatherMigrationClassesFromDirectory($directory, $namespace);

        $jobsTableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME);

        $this->assertFalse($this->schemaManager->tablesExist($jobsTableName));

        $migrator->runMigrationClasses($migrationClasses);

        $this->assertTrue($this->schemaManager->tablesExist($jobsTableName));
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
