<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\State;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\accounting\Helpers\Network
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 */
class RepositoryTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected Stub $loggerServiceStub;
    protected Migrator $migrator;
    protected Stub $factoryStub;
    protected Stub $payloadStub;
    protected Stub $jobStub;
    protected Store $jobsStore;
    protected string $jobsTableName;

    /**
     * @throws StoreException
     */
    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->loggerServiceStub = $this->createStub(Logger::class);

        $this->migrator = new Migrator($this->connection, $this->loggerServiceStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->payloadStub = $this->createStub(AbstractPayload::class);
        $this->jobStub = $this->createStub(GenericJob::class);
        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobStub->method('getType')->willReturn(GenericJob::class);
        $this->jobStub->method('getCreatedAt')->willReturn(new DateTimeImmutable());

        $this->jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerServiceStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );

        $this->jobsTableName = $this->connection->preparePrefixedTableName(
            TableConstants::TABLE_NAME_JOB
        );
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanInsertAndGetJob(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertNull($repository->getNext());

        $repository->insert($this->jobStub);

        $this->assertNotNull($repository->getNext());
    }

    /**
     * @throws StoreException
     */
    public function testInsertThrowsIfJobsStoreSetupNotRan(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        //$this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $repository->insert($this->jobStub);
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testInsertThrowsForInvalidJobType(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $invalidType = str_pad('abc', TableConstants::COLUMN_TYPE_LENGTH + 1);
        $jobStub = $this->createStub(GenericJob::class);
        $jobStub->method('getPayload')->willReturn($this->payloadStub);
        $jobStub->method('getType')->willReturn($invalidType);
        $jobStub->method('getCreatedAt')->willReturn(new DateTimeImmutable());

        $repository->insert($jobStub);
    }

    /**
     * @throws StoreException
     */
    public function testGetNextThrowsIfJobsStoreSetupNotRan(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        //$this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $repository->getNext();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testGetNextThrowsForInvalidJobType(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $payloadStub = $this->createStub(AbstractPayload::class);
        $jobStub = $this->createStub(AbstractJob::class); // Abstract classes can't be initialized...
        $jobStub->method('getPayload')->willReturn($payloadStub);
        $jobStub->method('getType')->willReturn(AbstractJob::class);
        $jobStub->method('getCreatedAt')->willReturn(new DateTimeImmutable());

        $repository->insert($jobStub);

        $this->expectException(StoreException::class);

        $repository->getNext();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     * @throws Exception
     */
    public function testCanDeleteJob(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertFalse($repository->delete(1));
        $repository->insert($this->jobStub);
        $job = $repository->getNext();
        if ($job === null) {
            throw new Exception('Invalid job.');
        }
        $jobId = $job->getId();
        if ($jobId === null) {
            throw new Exception('Invalid job ID.');
        }
        $this->assertTrue($repository->delete($jobId));
        $this->assertFalse($repository->delete($jobId));
    }

    /**
     * @throws StoreException
     */
    public function testDeleteThrowsWhenJobsStoreSetupNotRan(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        //$this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $repository->delete(1);
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanGetSpecificJobType(): void
    {
        $repository = new Repository($this->connection, $this->jobsTableName, $this->loggerServiceStub);
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertNull($repository->getNext());

        $repository->insert($this->jobStub);

        $authenticationEvent = new Event(new State\Saml2(StateArrays::SAML2_FULL));
        $authenticationEventJob = new Event\Job($authenticationEvent);

        $repository->insert($authenticationEventJob);

        $this->assertInstanceOf(Event\Job::class, $repository->getNext(Event\Job::class));
    }

    public function testInitializationThrowsForInvalidJobsTableName(): void
    {
        $this->expectException(StoreException::class);

        new Repository($this->connection, 'invalid-table-name', $this->loggerServiceStub);
    }
}