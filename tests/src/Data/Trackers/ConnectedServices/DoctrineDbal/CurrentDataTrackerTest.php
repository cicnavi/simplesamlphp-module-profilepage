<?php

namespace SimpleSAML\Test\Module\accounting\Data\Trackers\ConnectedServices\DoctrineDbal;

use DateInterval;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;
use SimpleSAML\Module\accounting\Data\Trackers\ConnectedServices\DoctrineDbal\CurrentDataTracker;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Module\accounting\Entities\ConnectedService;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Trackers\ConnectedServices\DoctrineDbal\CurrentDataTracker
 * @uses \SimpleSAML\Module\accounting\Data\Providers\ConnectedServices\DoctrineDbal\CurrentDataProvider
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 */
class CurrentDataTrackerTest extends TestCase
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
            CurrentDataTracker::class,
            new CurrentDataTracker(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->store
            )
        );

        $this->assertInstanceOf(
            CurrentDataTracker::class,
            new CurrentDataTracker($this->moduleConfigurationStub, $this->loggerMock)
        );

        $this->assertInstanceOf(
            CurrentDataTracker::class,
            CurrentDataTracker::build($this->moduleConfigurationStub, $this->loggerMock)
        );
    }

    /**
     * @throws StoreException
     */
    public function testProcessCallsPersistOnDataStore(): void
    {
        $authenticationEventStub = $this->createStub(Event::class);

        $this->store->expects($this->once())
            ->method('persist')
            ->with($authenticationEventStub);

        $tracker = new CurrentDataTracker(
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

        $tracker = new CurrentDataTracker(
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

        $tracker = new CurrentDataTracker(
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

        $tracker = new CurrentDataTracker(
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

        $tracker = new CurrentDataTracker(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->store
        );

        $tracker->enforceDataRetentionPolicy($retentionPolicy);
    }
}