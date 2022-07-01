<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 */
class JobsStoreTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected \PHPUnit\Framework\MockObject\Stub $factory;
    protected Connection $connection;

    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $this->factory = $this->createStub(Factory::class);
        $this->factory->method('buildConnection')->willReturn($this->connection);
    }

    public function testCanGetPrefixedTableNames(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new JobsStore($this->moduleConfiguration, $this->factory);

        $tableNameJobs = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_JOBS);
        $tableNameFailedJobs = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_FAILED_JOBS);

        $this->assertSame($tableNameJobs, $jobsStore->getPrefixedTableNameJobs());
        $this->assertSame($tableNameFailedJobs, $jobsStore->getPrefixedTableNameFailedJobs());
    }
}
