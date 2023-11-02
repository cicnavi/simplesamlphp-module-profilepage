<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Providers\ConnectedServices\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\ConnectedServices\DoctrineDbal\CurrentDataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;
use SimpleSAML\Module\accounting\Entities\ConnectedService\Bag;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Providers\ConnectedServices\DoctrineDbal\CurrentDataProvider
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer
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
    protected $connectedServicesBagMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->moduleConfigurationMock->method('getSerializerClass')->willReturn(PhpSerializer::class);
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationMock->method('getConnectionParameters')
            ->willReturn($connectionParams);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->storeMock = $this->createMock(Store::class);

        $this->connectedServicesBagMock = $this->createMock(Bag::class);
    }

    /**
     * @throws StoreException
     */
    protected function prepareMockedInstance(): CurrentDataProvider
    {
        return new CurrentDataProvider(
            $this->moduleConfigurationMock,
            $this->loggerMock,
            ModuleConfiguration\ConnectionType::SLAVE,
            $this->storeMock
        );
    }

    /**
     * @throws StoreException
     */
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
    public function testNeedsSetupWhenTrue(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);
        $this->assertTrue($this->prepareMockedInstance()->needsSetup());
    }

    /**
     * @throws StoreException
     */
    public function testNeedsSetupWhenFalse(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);
        $this->assertFalse($this->prepareMockedInstance()->needsSetup());
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testRunSetupLogsWarningWhenNotNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);
        $this->loggerMock->expects($this->once())->method('warning');

        $this->prepareMockedInstance()->runSetup();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testCanRunSetupWhenNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);
        $this->storeMock->expects($this->once())->method('runSetup');

        $this->prepareMockedInstance()->runSetup();
    }

    /**
     * @throws StoreException
     */
    public function testCanGetConnectedServices(): void
    {
        $this->storeMock->expects($this->once())->method('getConnectedServices')->with('userId');

        $this->prepareMockedInstance()->getConnectedServices('userId');
    }

    /**
     * @throws StoreException
     */
    public function testCanGetTracker(): void
    {
        $this->assertInstanceOf(
            CurrentDataProvider::class,
            $this->prepareMockedInstance()->getTracker()
        );
    }
}
