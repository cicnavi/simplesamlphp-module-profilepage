<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Builders;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Stores\Builders\DataStoreBuilder;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer
 */
class DataStoreBuilderTest extends TestCase
{
    protected Stub $moduleConfigurationStub;
    protected Stub $loggerStub;
    protected DataStoreBuilder $dataStoreBuilder;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->moduleConfigurationStub->method('getSerializerClass')->willReturn(PhpSerializer::class);

        $this->loggerStub = $this->createStub(LoggerInterface::class);

        $this->helpersManager = new HelpersManager();

        $this->dataStoreBuilder = new DataStoreBuilder(
            $this->moduleConfigurationStub,
            $this->loggerStub,
            $this->helpersManager
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanBuildDataStore(): void
    {
        $this->assertInstanceOf(Store::class, $this->dataStoreBuilder->build(Store::class));
    }

    public function testThrowsForInvalidDataStoreClass(): void
    {
        $this->expectException(StoreException::class);
        $this->dataStoreBuilder->build(ModuleConfiguration::class);
    }
}
