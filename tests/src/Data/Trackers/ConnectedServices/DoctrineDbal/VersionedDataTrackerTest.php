<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Trackers\ConnectedServices\DoctrineDbal;

use DateInterval;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\profilepage\Data\Trackers\ConnectedServices\DoctrineDbal\VersionedDataTracker;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\ConnectedService;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Trackers\ConnectedServices\DoctrineDbal\VersionedDataTracker
 * @uses \SimpleSAML\Module\profilepage\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Helpers\Hash
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
 */
class VersionedDataTrackerTest extends TestCase
{
    /**
     * @var Stub
     */
    protected $moduleConfigurationStub;
    /**
     * @var MockObject
     */
    protected $loggerMock;
    /**
     * @var MockObject
     */
    protected $store;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->moduleConfigurationStub->method('getSerializerClass')->willReturn(PhpSerializer::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->store = $this->createMock(
            Store::class
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            VersionedDataTracker::class,
            new VersionedDataTracker(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->store
            )
        );

        $this->assertInstanceOf(
            VersionedDataTracker::class,
            new VersionedDataTracker($this->moduleConfigurationStub, $this->loggerMock)
        );

        $this->assertInstanceOf(
            VersionedDataTracker::class,
            VersionedDataTracker::build($this->moduleConfigurationStub, $this->loggerMock)
        );
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testProcessCallsPersistOnDataStore(): void
    {
        $authenticationEventStub = $this->createStub(Event::class);

        $this->store->expects($this->once())
            ->method('persist')
            ->with($authenticationEventStub);

        $tracker = new VersionedDataTracker(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->store
        );

        $tracker->process($authenticationEventStub);
    }

    /**
     * @throws StoreException
     */
    public function testSetupDependsOnDataStore(): void
    {
        $this->store->expects($this->exactly(2))
            ->method('needsSetup')
            ->willReturn(true);

        $this->store->expects($this->once())
            ->method('runSetup');

        $tracker = new VersionedDataTracker(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->store
        );

        $this->assertTrue($tracker->needsSetup());

        $tracker->runSetup();
    }

    /**
     * @throws StoreException
     */
    public function testRunningSetupIfNotNeededLogsWarning(): void
    {
        $this->store->method('needsSetup')
            ->willReturn(false);

        $this->loggerMock->expects($this->once())
            ->method('warning');

        $tracker = new VersionedDataTracker(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->store
        );

        $tracker->runSetup();
    }

    /**
     * @throws StoreException
     */
    public function testGetConnectedServices(): void
    {
        $connectedOrganizationsBagStub = $this->createStub(ConnectedService\Bag::class);
        $this->store->expects($this->once())
            ->method('getConnectedServices')
            ->willReturn($connectedOrganizationsBagStub);

        $tracker = new VersionedDataTracker(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->store
        );

        $this->assertInstanceOf(
            ConnectedService\Bag::class,
            $tracker->getConnectedServices('test')
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanEnforceDataRetentionPolicy(): void
    {
        $retentionPolicy = new DateInterval('P10D');

        $this->store->expects($this->once())
            ->method('deleteDataOlderThan');

        $tracker = new VersionedDataTracker(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->store
        );

        $tracker->enforceDataRetentionPolicy($retentionPolicy);
    }
}
