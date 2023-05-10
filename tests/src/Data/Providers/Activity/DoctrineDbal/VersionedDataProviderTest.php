<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Providers\Activity\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 */
class VersionedDataProviderTest extends TestCase
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

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->storeMock = $this->createMock(Store::class);
    }

    protected function prepareMockedInstance(): VersionedDataProvider
    {
        return new VersionedDataProvider(
            $this->moduleConfigurationMock,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::SLAVE,
            $this->storeMock
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VersionedDataProvider::class, $this->prepareMockedInstance());

        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationMock->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->assertInstanceOf(VersionedDataProvider::class, VersionedDataProvider::build(
            $this->moduleConfigurationMock,
            $this->loggerMock,
        ));
    }

    public function testNeedsSetup(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);
        $this->assertTrue($this->prepareMockedInstance()->needsSetup());
    }

    public function testDoesNotNeedsSetup(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);
        $this->assertFalse($this->prepareMockedInstance()->needsSetup());
    }

    public function testSetupDoesNotRunWhenNotNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('warning');
        $this->storeMock->expects($this->never())
            ->method('runSetup');

        $this->prepareMockedInstance()->runSetup();
    }

    public function testSetupRunsWhenNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);
        $this->storeMock->expects($this->once())->method('runSetup');
        $this->prepareMockedInstance()->runSetup();
    }

    public function testGetsActivityFromStore(): void
    {
        $this->storeMock->expects($this->once())
            ->method('getActivity')
            ->with('1', 2, 3);

        $this->prepareMockedInstance()->getActivity('1', 2, 3);
    }

    public function testCanGetTracker(): void
    {
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationMock->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->assertInstanceOf(
            DataTrackerInterface::class,
            $this->prepareMockedInstance()->getTracker()
        );
    }
}
