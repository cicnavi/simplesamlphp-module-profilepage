<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

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
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\accounting\Helpers\NetworkHelper
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
    protected Store $jobsStore;
    protected string $jobsTableName;

    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
        $this->connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->loggerServiceStub = $this->createStub(Logger::class);

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
        $this->jobsStore = new Store($this->moduleConfiguration, $this->loggerServiceStub, $this->factoryStub);

        $this->jobsTableName = $this->connection->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_JOB);
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

        $invalidType = str_pad('abc', Store\TableConstants::COLUMN_TYPE_LENGTH + 1);
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

        $authenticationEvent = new Event(new State(StateArrays::FULL));
        $authenticationEventJob = new Event\Job($authenticationEvent);

        $repository->insert($authenticationEventJob);

        $this->assertInstanceOf(Event\Job::class, $repository->getNext(Event\Job::class));
    }

    public function testInitializationThrowsForInvalidJobsTableName(): void
    {
        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        new Repository($this->connection, 'invalid-table-name', $this->loggerServiceStub);
    }
}
