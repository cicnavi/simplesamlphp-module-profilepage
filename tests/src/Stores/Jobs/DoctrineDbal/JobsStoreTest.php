<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000000CreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000100CreateFailedJobsTable
 */
class JobsStoreTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected \PHPUnit\Framework\MockObject\Stub $factoryStub;
    protected Connection $connection;
    protected \PHPUnit\Framework\MockObject\Stub $loggerServiceStub;
    protected Migrator $migrator;

    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->loggerServiceStub = $this->createStub(LoggerService::class);

        /** @psalm-suppress InvalidArgument */
        $this->migrator = new Migrator($this->connection, $this->loggerServiceStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);
    }

    public function testSetupDependsOnMigratorSetup(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new JobsStore($this->moduleConfiguration, $this->factoryStub, $this->loggerServiceStub);

        $this->assertTrue($this->migrator->needsSetup());
        $this->assertTrue($jobsStore->needsSetup());

        $jobsStore->runSetup();

        $this->assertFalse($jobsStore->needsSetup());
        $this->assertFalse($this->migrator->needsSetup());
    }

    public function testSetupDependsOnMigrations(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new JobsStore($this->moduleConfiguration, $this->factoryStub, $this->loggerServiceStub);

        // Run migrator setup beforehand, so it only depends on JobsStore migrations setup
        $this->migrator->runSetup();
        $this->assertTrue($jobsStore->needsSetup());

        $jobsStore->runSetup();

        $this->assertFalse($jobsStore->needsSetup());
    }

    public function testCanGetPrefixedTableNames(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new JobsStore($this->moduleConfiguration, $this->factoryStub, $this->loggerServiceStub);

        $tableNameJobs = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_JOBS);
        $tableNameFailedJobs = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_FAILED_JOBS);

        $this->assertSame($tableNameJobs, $jobsStore->getPrefixedTableNameJobs());
        $this->assertSame($tableNameFailedJobs, $jobsStore->getPrefixedTableNameFailedJobs());
    }

    public function testCanEnqueueJob(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new JobsStore($this->moduleConfiguration, $this->factoryStub, $this->loggerServiceStub);
        $jobsStore->runSetup();

        $payloadStub = $this->createStub(AbstractPayload::class);
        $jobStub = $this->createStub(AbstractJob::class);
        $jobStub->method('getPayload')->willReturn($payloadStub);

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs())->fetchOne();

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->enqueue($jobStub);

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->enqueue($jobStub);
        $jobsStore->enqueue($jobStub);

        $this->assertSame(3, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    public function testEnqueueThrowsStoreException(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new JobsStore($this->moduleConfiguration, $this->factoryStub, $this->loggerServiceStub);
        // Don't run setup, so we get exception
        //$jobsStore->runSetup();

        $payloadStub = $this->createStub(AbstractPayload::class);
        $jobStub = $this->createStub(AbstractJob::class);
        $jobStub->method('getPayload')->willReturn($payloadStub);

        $this->expectException(StoreException::class);

        $jobsStore->enqueue($jobStub);
    }
}
