<?php

namespace SimpleSAML\Test\Module\accounting\Data\Providers\Activity\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\CurrentDataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store;
use SimpleSAML\Module\accounting\Data\Trackers\Activity\DoctrineDbal\CurrentDataTracker;
use SimpleSAML\Module\accounting\Entities\Activity\Bag;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\CurrentDataProvider
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 */
class CurrentDataProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $moduleConfigurationMock;
    /**
     * @var MockObject
     */
    protected $loggerMock;
    /**
     * @var MockObject
     */
    protected $storeMock;
    /**
     * @var MockObject
     */
    protected $activityBagMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationMock->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->storeMock = $this->createMock(Store::class);

        $this->activityBagMock = $this->createMock(Bag::class);
    }

    protected function prepareMockedInstance(): CurrentDataProvider
    {
        return new CurrentDataProvider(
            $this->moduleConfigurationMock,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::SLAVE,
            $this->storeMock
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CurrentDataProvider::class, $this->prepareMockedInstance());
    }

    /**
     * @throws StoreException
     */
    public function testCanSelfBuild(): void
    {
        $this->assertInstanceOf(
            CurrentDataProvider::class,
            CurrentDataProvider::build($this->moduleConfigurationMock, $this->loggerMock)
        );
    }

    /**
     * @throws StoreException
     */
    public function testNeedsSetupReturnsTrue(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);

        $this->assertTrue($this->prepareMockedInstance()->needsSetup());
    }

    /**
     * @throws StoreException
     */
    public function testNeedsSetupReturnsFalse(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);

        $this->assertFalse($this->prepareMockedInstance()->needsSetup());
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testRunSetupLogsWarningIfNotNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);
        $this->loggerMock->expects($this->once())->method('warning');

        $this->prepareMockedInstance()->runSetup();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testRunSetupIfNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);
        $this->storeMock->expects($this->once())->method('runSetup');

        $this->prepareMockedInstance()->runSetup();
    }

    /**
     * @throws StoreException
     */
    public function testGetActivitiy(): void
    {
        $this->storeMock->expects($this->once())
            ->method('getActivity')
            ->with('userId', 10, 0)
            ->willReturn($this->activityBagMock);

        $this->prepareMockedInstance()->getActivity('userId', 10, 0);
    }

    /**
     * @throws StoreException
     */
    public function testGetTracker(): void
    {
        $this->assertInstanceOf(
            CurrentDataTracker::class,
            $this->prepareMockedInstance()->getTracker()
        );
    }
}