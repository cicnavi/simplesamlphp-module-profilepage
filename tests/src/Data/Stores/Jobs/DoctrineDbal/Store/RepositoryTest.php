<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Repository;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob;
use SimpleSAML\Module\profilepage\Entities\GenericJob;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Logger;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
 */
class RepositoryTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected Stub $loggerServiceStub;
    protected Migrator $migrator;
    protected Stub $factoryStub;
    protected array $payload = StateArrays::SAML2_FULL;
    protected Stub $jobStub;
    protected Store $jobsStore;
    protected string $jobsTableName;
    protected MockObject $serializerMock;

    /**
     * @throws StoreException
     */
    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_profilepage.php');
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->loggerServiceStub = $this->createStub(Logger::class);

        $this->migrator = new Migrator($this->connection, $this->loggerServiceStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->jobStub = $this->createStub(GenericJob::class);
        $this->jobStub->method('getRawState')->willReturn($this->payload);
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

        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->serializerMock->method('do')->will($this->returnCallback(
            fn($argument) => serialize($argument)
        ));
        $this->serializerMock->method('undo')->will($this->returnCallback(
            fn($argument) => unserialize($argument)
        ));
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanInsertAndGetJob(): void
    {
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
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
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
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
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $invalidType = str_pad('abc', TableConstants::COLUMN_TYPE_LENGTH + 1);
        $jobStub = $this->createStub(GenericJob::class);
        $jobStub->method('getRawState')->willReturn($this->payload);
        $jobStub->method('getType')->willReturn($invalidType);
        $jobStub->method('getCreatedAt')->willReturn(new DateTimeImmutable());

        $repository->insert($jobStub);
    }

    /**
     * @throws StoreException
     */
    public function testGetNextThrowsIfJobsStoreSetupNotRan(): void
    {
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
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
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $jobStub = $this->createStub(AbstractJob::class); // Abstract classes can't be initialized...
        $jobStub->method('getRawState')->willReturn(StateArrays::SAML2_FULL);
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
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
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
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
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
        $repository = new Repository(
            $this->connection,
            $this->jobsTableName,
            $this->loggerServiceStub,
            $this->serializerMock
        );
        // Running setup will ensure that all migrations are ran.
        $this->jobsStore->runSetup();

        $this->assertNull($repository->getNext());

        $repository->insert($this->jobStub);

        $authenticationEventJob = new Event\Job($this->payload);

        $repository->insert($authenticationEventJob);

        $this->assertInstanceOf(Event\Job::class, $repository->getNext(Event\Job::class));
    }

    public function testInitializationThrowsForInvalidJobsTableName(): void
    {
        $this->expectException(StoreException::class);

        new Repository(
            $this->connection,
            'invalid-table-name',
            $this->loggerServiceStub,
            $this->serializerMock
        );
    }
}
