<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Jobs\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
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
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000100CreateJobFailedTable
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Bases\AbstractCreateJobsTable
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
 */
class StoreTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;
    protected Stub $factoryStub;
    protected Connection $connection;
    protected Stub $loggerStub;
    protected Migrator $migrator;
    protected array $payload = StateArrays::SAML2_FULL;
    protected Stub $jobStub;

    /**
     * @throws StoreException
     */
    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_profilepage.php');
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->loggerStub = $this->createStub(Logger::class);

        $this->migrator = new Migrator($this->connection, $this->loggerStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->jobStub = $this->createStub(GenericJob::class);
        $this->jobStub->method('getRawState')->willReturn($this->payload);
        $this->jobStub->method('getType')->willReturn(GenericJob::class);
        $this->jobStub->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $this->jobStub->method('getId')->willReturn(1);
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testSetupDependsOnMigratorSetup(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );

        $this->assertTrue($this->migrator->needsSetup());
        $this->assertTrue($jobsStore->needsSetup());

        $jobsStore->runSetup();

        $this->assertFalse($jobsStore->needsSetup());
        $this->assertFalse($this->migrator->needsSetup());
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testSetupDependsOnMigrations(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );

        // Run migrator setup beforehand, so it only depends on Store migrations setup
        $this->migrator->runSetup();
        $this->assertTrue($jobsStore->needsSetup());

        $jobsStore->runSetup();

        $this->assertFalse($jobsStore->needsSetup());
    }

    /**
     * @throws StoreException
     */
    public function testCanGetPrefixedTableNames(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );

        $tableNameJobs = $this->connection->preparePrefixedTableName(
            TableConstants::TABLE_NAME_JOB
        );
        $tableNameFailedJobs = $this->connection->preparePrefixedTableName(
            TableConstants::TABLE_NAME_JOB_FAILED
        );

        $this->assertSame($tableNameJobs, $jobsStore->getPrefixedTableNameJobs());
        $this->assertSame($tableNameFailedJobs, $jobsStore->getPrefixedTableNameFailedJobs());
    }

    /**
     * @throws StoreException
     */
    public function testCanBuildInstanceStatically(): void
    {
        $moduleConfiguration = $this->createStub(ModuleConfiguration::class);
        $moduleConfiguration->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $moduleConfiguration->method('getSerializerClass')->willReturn(PhpSerializer::class);
        $this->assertInstanceOf(Store::class, Store::build($moduleConfiguration, $this->loggerStub));
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testCanEnqueueJob(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
        $jobsStore->runSetup();

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs());

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->enqueue($this->jobStub);

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->enqueue($this->jobStub);
        $jobsStore->enqueue($this->jobStub);

        $this->assertSame(3, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    /**
     * @throws StoreException
     */
    public function testEnqueueThrowsStoreExceptionOnNonSetupRun(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
        // Don't run setup, so we get exception
        //$jobsStore->runSetup();

        $jobStub = $this->createStub(AbstractJob::class);
        $jobStub->method('getRawState')->willReturn(StateArrays::SAML2_FULL);

        $this->expectException(StoreException::class);

        $jobsStore->enqueue($jobStub);
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testCanDequeueJob(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
        $jobsStore->runSetup();

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs())->fetchOne();

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->enqueue($this->jobStub);
        $jobsStore->enqueue($this->jobStub);

        $this->assertSame(2, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->dequeue($this->jobStub->getType());

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testCanDequeueSpecificJobType(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
        $jobsStore->runSetup();

        $authenticationEventJob = new Event\Job(StateArrays::SAML2_FULL);

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')->from($jobsStore->getPrefixedTableNameJobs())->fetchOne();

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->enqueue($this->jobStub);
        $jobsStore->enqueue($authenticationEventJob);

        $this->assertSame(2, (int) $queryBuilder->executeQuery()->fetchOne());

        $this->assertInstanceOf(Event\Job::class, $jobsStore->dequeue(Event\Job::class));

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());

        $this->assertNull($jobsStore->dequeue(Event::class));

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testDequeueThrowsWhenSetupNotRun(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
//        $jobsStore->runSetup();

        $jobStub = $this->createStub(AbstractJob::class);
        $jobStub->method('getRawState')->willReturn(StateArrays::SAML2_FULL);

        $this->expectException(StoreException::class);

        $jobsStore->dequeue('test-type');
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testDequeueThrowsForJobWithInvalidId(): void
    {
        $repositoryStub = $this->createStub(Repository::class);
        $jobStub = $this->createStub(GenericJob::class);
        $jobStub->method('getRawState')->willReturn($this->payload);
        $jobStub->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $jobStub->method('getType')->willReturn(GenericJob::class);
        $jobStub->method('getId')->willReturn(null); // Invalid ID value...

        $repositoryStub->method('getNext')->willReturn($jobStub);

        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $repositoryStub
        );
        $jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $jobsStore->dequeue($this->jobStub->getType());
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     * @throws Exception
     */
    public function testDequeThrowsAfterMaxDeleteAttempts(): void
    {
        $repositoryStub = $this->createStub(Repository::class);
        $repositoryStub->method('getNext')->willReturn($this->jobStub);
        $repositoryStub->method('delete')->willReturn(false);

        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $repositoryStub
        );
        $jobsStore->runSetup();

        $this->expectException(StoreException::class);

        $jobsStore->dequeue($this->jobStub->getType());
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     * @throws Exception
     */
    public function testCanContinueSearchingInCaseOfJobDeletion(): void
    {
        $repositoryStub = $this->createStub(Repository::class);
        $repositoryStub->method('getNext')->willReturn($this->jobStub);
        $repositoryStub->method('delete')->willReturnOnConsecutiveCalls(false, true);

        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $repositoryStub
        );
        $jobsStore->runSetup();

        $this->assertNotNull($jobsStore->dequeue($this->jobStub->getType()));
    }

    /**
     * @throws StoreException
     * @throws Exception
     * @throws MigrationException
     */
    public function testCanMarkFailedJob(): void
    {
        $jobsStore = new Store(
            $this->moduleConfiguration,
            $this->loggerStub,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
        $jobsStore->runSetup();

        $queryBuilder = $this->connection->dbal()->createQueryBuilder();
        $queryBuilder->select('COUNT(id) as jobsCount')
            ->from($jobsStore->getPrefixedTableNameFailedJobs())
            ->fetchOne();

        $this->assertSame(0, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->markFailedJob($this->jobStub);

        $this->assertSame(1, (int) $queryBuilder->executeQuery()->fetchOne());

        $jobsStore->markFailedJob($this->jobStub);

        $this->assertSame(2, (int) $queryBuilder->executeQuery()->fetchOne());
    }
}
