<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store
 * @covers \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\accounting\Helpers\NetworkHelper
 */
class StoreTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected \PHPUnit\Framework\MockObject\Stub $factoryStub;
    protected Connection $connection;
    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;
    protected Migrator $migrator;
    protected \PHPUnit\Framework\MockObject\Stub $payloadStub;
    protected \PHPUnit\Framework\MockObject\Stub $jobStub;

    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->loggerStub = $this->createStub(Logger::class);

        /** @psalm-suppress InvalidArgument */
        $this->migrator = new Migrator($this->connection, $this->loggerStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->payloadStub = $this->createStub(AbstractPayload::class);
        $this->jobStub = $this->createStub(GenericJob::class);
        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobStub->method('getType')->willReturn(GenericJob::class);
        $this->jobStub->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $this->jobStub->method('getId')->willReturn(1);
    }

    public function testSetupDependsOnMigratorSetup(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);

        $this->assertTrue($this->migrator->needsSetup());
        $this->assertTrue($jobsStore->needsSetup());

        $jobsStore->runSetup();

        $this->assertFalse($jobsStore->needsSetup());
        $this->assertFalse($this->migrator->needsSetup());
    }

    public function testSetupDependsOnMigrations(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);

        // Run migrator setup beforehand, so it only depends on Store migrations setup
        $this->migrator->runSetup();
        $this->assertTrue($jobsStore->needsSetup());

        $jobsStore->runSetup();

        $this->assertFalse($jobsStore->needsSetup());
    }

    public function testCanGetPrefixedTableNames(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);

        $tableNameJobs = $this->connection->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOB);
        $tableNameFailedJobs = $this->connection->preparePrefixedTableName(
            Store\TableConstants::TABLE_NAME_JOB_FAILED
        );

        $this->assertSame($tableNameJobs, $jobsStore->getPrefixedTableNameJobs());
        $this->assertSame($tableNameFailedJobs, $jobsStore->getPrefixedTableNameFailedJobs());
    }

    public function testCanBuildInstanceStatically(): void
    {
        $moduleConfiguration = $this->createStub(ModuleConfiguration::class);
        $moduleConfiguration->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(Store::class, Store::build($moduleConfiguration, $this->loggerStub));
    }

    public function testCanEnqueueJob(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);
        $jobsStore->runSetup();

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs());

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        /** @psalm-suppress InvalidArgument */
        $jobsStore->enqueue($this->jobStub);

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());

        /** @psalm-suppress InvalidArgument */
        $jobsStore->enqueue($this->jobStub);
        /** @psalm-suppress InvalidArgument */
        $jobsStore->enqueue($this->jobStub);

        $this->assertSame(3, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    public function testEnqueueThrowsStoreExceptionOnNonSetupRun(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);
        // Don't run setup, so we get exception
        //$jobsStore->runSetup();

        $payloadStub = $this->createStub(AbstractPayload::class);
        $jobStub = $this->createStub(AbstractJob::class);
        $jobStub->method('getPayload')->willReturn($payloadStub);

        $this->expectException(StoreException::class);

        $jobsStore->enqueue($jobStub);
    }

    public function testCanDequeueJob(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);
        $jobsStore->runSetup();

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs())->fetchOne();

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        /** @psalm-suppress InvalidArgument */
        $jobsStore->enqueue($this->jobStub);
        /** @psalm-suppress InvalidArgument */
        $jobsStore->enqueue($this->jobStub);

        $this->assertSame(2, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->dequeue();

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    public function testCanDequeueSpecificJobType(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);
        $jobsStore->runSetup();

        $authenticationEvent = new Event(new State(StateArrays::FULL));
        $authenticationEventJob = new Event\Job($authenticationEvent);

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs())->fetchOne();

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        /** @psalm-suppress InvalidArgument */
        $jobsStore->enqueue($this->jobStub);
        $jobsStore->enqueue($authenticationEventJob);

        $this->assertSame(2, (int) $queryBuilder->executeQuery()->fetchOne());

        $this->assertInstanceOf(Event\Job::class, $jobsStore->dequeue(Event\Job::class));

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());

        $this->assertNull($jobsStore->dequeue(Event::class));

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    public function testDequeueThrowsWhenSetupNotRun(): void
    {
        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store($this->moduleConfiguration, $this->loggerStub, $this->factoryStub);
//        $jobsStore->runSetup();

        $payloadStub = $this->createStub(AbstractPayload::class);
        $jobStub = $this->createStub(AbstractJob::class);
        $jobStub->method('getPayload')->willReturn($payloadStub);

        $this->expectException(StoreException::class);

        $jobsStore->dequeue('test-type');
    }

    public function testDequeueThrowsForJobWithInvalidId(): void
    {
        $repositoryStub = $this->createStub(Store\Repository::class);
        $jobStub = $this->createStub(GenericJob::class);
        $jobStub->method('getPayload')->willReturn($this->payloadStub);
        $jobStub->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $jobStub->method('getType')->willReturn(GenericJob::class);
        $jobStub->method('getId')->willReturn(null); // Invalid ID value...

        $repositoryStub->method('getNext')->willReturn($jobStub);

        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            $this->factoryStub,
            null,
            $repositoryStub
        );
        $jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $jobsStore->dequeue();
    }

    public function testDequeThrowsAfterMaxDeleteAttempts(): void
    {
        $repositoryStub = $this->createStub(Store\Repository::class);
        $repositoryStub->method('getNext')->willReturn($this->jobStub);
        $repositoryStub->method('delete')->willReturn(false);

        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            $this->factoryStub,
            null,
            $repositoryStub
        );
        $jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $jobsStore->dequeue();
    }

    public function testCanContinueSearchingInCaseOfJobDeletion(): void
    {
        $repositoryStub = $this->createStub(Store\Repository::class);
        $repositoryStub->method('getNext')->willReturn($this->jobStub);
        $repositoryStub->method('delete')->willReturnOnConsecutiveCalls(false, true);

        /** @psalm-suppress InvalidArgument */
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            $this->factoryStub,
            null,
            $repositoryStub
        );
        $jobsStore->runSetup();

        $this->assertNotNull($jobsStore->dequeue());
    }
}
