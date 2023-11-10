<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Providers\Activity\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\profilepage\Data\Trackers\Activity\DoctrineDbal\VersionedDataTracker;
use SimpleSAML\Module\profilepage\Entities\Activity\Bag;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
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
    protected $activityBagMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->moduleConfigurationMock->method('getSerializerClass')->willReturn(PhpSerializer::class);
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationMock->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->storeMock = $this->createMock(Store::class);

        $this->activityBagMock = $this->createMock(Bag::class);
    }

    /**
     * @throws StoreException
     */
    protected function mocked(): VersionedDataProvider
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
        $this->assertInstanceOf(VersionedDataProvider::class, $this->mocked());
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
    public function testNeedsSetupReturnsTrue(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);

        $this->assertTrue($this->mocked()->needsSetup());
    }

    /**
     * @throws StoreException
     */
    public function testNeedsSetupReturnsFalse(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);

        $this->assertFalse($this->mocked()->needsSetup());
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testRunSetupLogsWarningIfNotNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(false);
        $this->loggerMock->expects($this->once())->method('warning');

        $this->mocked()->runSetup();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function testRunSetupIfNeeded(): void
    {
        $this->storeMock->method('needsSetup')->willReturn(true);
        $this->storeMock->expects($this->once())->method('runSetup');

        $this->mocked()->runSetup();
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

        $this->mocked()->getActivity('userId', 10, 0);
    }

    /**
     * @throws StoreException
     */
    public function testGetTracker(): void
    {
        $this->assertInstanceOf(
            VersionedDataTracker::class,
            $this->mocked()->getTracker()
        );
    }
}
