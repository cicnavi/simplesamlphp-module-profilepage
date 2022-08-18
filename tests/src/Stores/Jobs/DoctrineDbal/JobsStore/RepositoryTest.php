<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;

use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Repository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\HttpCache\Store;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Repository
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000000CreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000100CreateFailedJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\RawJob
 * @uses \SimpleSAML\Module\accounting\Entities\AuthenticationEvent
 * @uses \SimpleSAML\Module\accounting\Entities\AuthenticationEvent\Job
 */
class RepositoryTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected \PHPUnit\Framework\MockObject\Stub $loggerServiceStub;
    protected Migrator $migrator;
    protected \PHPUnit\Framework\MockObject\Stub $factoryStub;
    protected \PHPUnit\Framework\MockObject\Stub $payloadStub;
    protected \PHPUnit\Framework\MockObject\Stub $jobStub;
    protected JobsStore $jobsStore;
    protected string $jobsTableName;

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

        $this->payloadStub = $this->createStub(AbstractPayload::class);
        $this->jobStub = $this->createStub(GenericJob::class);
        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobStub->method('getType')->willReturn(GenericJob::class);
        $this->jobStub->method('getCreatedAt')->willReturn(new \DateTimeImmutable());

        /** @psalm-suppress InvalidArgument */
        $this->jobsStore = new JobsStore($this->moduleConfiguration, $this->factoryStub, $this->loggerServiceStub);

        $this->jobsTableName = $this->connection->preparePrefixedTableName(JobsStore::TABLE_NAME_JOBS);
    }

    public function testCanInsertAndGetJob(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertNull($repository->getNext());

        /** @psalm-suppress InvalidArgument */
        $repository->insert($this->jobStub);

        $this->assertNotNull($repository->getNext());
    }

    public function testInsertThrowsIfJobsStoreSetupNotRan(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        //$this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        $repository->insert($this->jobStub);
    }

    public function testInsertThrowsForInvalidJobType(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $invalidType = str_pad('abc', JobsStore::COLUMN_LENGTH_TYPE + 1);
        $jobStub = $this->createStub(GenericJob::class);
        $jobStub->method('getPayload')->willReturn($this->payloadStub);
        $jobStub->method('getType')->willReturn($invalidType);
        $jobStub->method('getCreatedAt')->willReturn(new \DateTimeImmutable());

        /** @psalm-suppress InvalidArgument */
        $repository->insert($jobStub);
    }

    public function testGetNextThrowsIfJobsStoreSetupNotRan(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        //$this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $repository->getNext();
    }

    public function testGetNextThrowsForInvalidJobType(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $payloadStub = $this->createStub(AbstractPayload::class);
        $jobStub = $this->createStub(AbstractJob::class); // Abstract classes can't be initialized..
        $jobStub->method('getPayload')->willReturn($payloadStub);
        $jobStub->method('getType')->willReturn(AbstractJob::class);
        $jobStub->method('getCreatedAt')->willReturn(new \DateTimeImmutable());

        /** @psalm-suppress InvalidArgument */
        $repository->insert($jobStub);

        $this->expectException(StoreException::class);

        $repository->getNext();
    }

    public function testCanDeleteJob(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertFalse($repository->delete(1));
        /** @psalm-suppress InvalidArgument */
        $repository->insert($this->jobStub);
        $job = $repository->getNext();
        if ($job === null) {
            throw new \Exception('Invalid job.');
        }
        $jobId = $job->getId();
        if ($jobId === null) {
            throw new \Exception('Invalid job ID.');
        }
        $this->assertTrue($repository->delete($jobId));
        $this->assertFalse($repository->delete($jobId));
    }

    public function testDeleteThrowsWhenJobsStoreSetupNotRan(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        //$this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $repository->delete(1);
    }

    public function testCanGetSpecificJobType(): void
    {
        /** @psalm-suppress InvalidArgument */
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertNull($repository->getNext());

        /** @psalm-suppress InvalidArgument */
        $repository->insert($this->jobStub);

        $authenticationEvent = new AuthenticationEvent(['sample-state']);
        $authenticationEventJob = new AuthenticationEvent\Job($authenticationEvent);

        $repository->insert($authenticationEventJob);

        $this->assertInstanceOf(AuthenticationEvent\Job::class, $repository->getNext(AuthenticationEvent\Job::class));
    }

    public function testInitializationThrowsForInvalidJobsTableName(): void
    {
        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        new Repository($this->connection, 'invalid-table-name', $this->loggerServiceStub);
    }
}
