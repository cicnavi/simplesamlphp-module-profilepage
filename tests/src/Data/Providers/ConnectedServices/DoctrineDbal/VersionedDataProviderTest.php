<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\profilepage\Entities\ConnectedService\Bag;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
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
    public function prepareMockedInstance(): VersionedDataProvider
    {
        return new VersionedDataProvider(
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
        $this->assertInstanceOf(VersionedDataProvider::class, $this->prepareMockedInstance());
    }

    /**
     * @throws StoreException
     */
    public function testCanSelfBuild(): void
    {
        $this->assertInstanceOf(
            VersionedDataProvider::class,
            VersionedDataProvider::build($this->moduleConfigurationMock, $this->loggerMock)
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
        $this->storeMock->method('getConnectedServices')->willReturn($this->connectedServicesBagMock);

        $this->assertInstanceOf(Bag::class, $this->prepareMockedInstance()->getConnectedServices('userId'));
    }

    /**
     * @throws StoreException
     */
    public function testCanGetTracker(): void
    {
        $this->assertInstanceOf(
            VersionedDataProvider::class,
            $this->prepareMockedInstance()->getTracker()
        );
    }
}
