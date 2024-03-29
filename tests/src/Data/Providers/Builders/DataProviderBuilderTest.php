<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Providers\Builders;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider as
    ActivityVersionedDataProviderAlias;
use SimpleSAML\Module\profilepage\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider as
    ConnectedServicesVersionedDataProviderAlias;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Providers\Builders\DataProviderBuilder
 * @uses \SimpleSAML\Module\profilepage\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
 */
class DataProviderBuilderTest extends TestCase
{
    protected Stub $moduleConfigurationStub;

    protected Stub $loggerStub;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getSerializerClass')->willReturn(PhpSerializer::class);
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->loggerStub = $this->createStub(LoggerInterface::class);
        $this->helpersManager = new HelpersManager();
    }

    protected function prepareMockedInstance(): DataProviderBuilder
    {
        return new DataProviderBuilder(
            $this->moduleConfigurationStub,
            $this->loggerStub,
            $this->helpersManager
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            DataProviderBuilder::class,
            $this->prepareMockedInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testCanBuildDataProvider(): void
    {
        $builder = $this->prepareMockedInstance();

        $this->assertInstanceOf(
            ActivityVersionedDataProviderAlias::class,
            $builder->build(ActivityVersionedDataProviderAlias::class)
        );
    }

    public function testThrowsForInvalidClass(): void
    {
        $this->expectException(Exception::class);

        $this->prepareMockedInstance()->build('invalid');
    }

    /**
     * @throws Exception
     */
    public function testCanBuildActivityProvider(): void
    {
        $this->assertInstanceOf(
            ActivityVersionedDataProviderAlias::class,
            $this->prepareMockedInstance()->buildActivityProvider(ActivityVersionedDataProviderAlias::class)
        );
    }

    /**
     * @throws Exception
     */
    public function testBuildActivityProviderThrowsForInvalidClass(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->prepareMockedInstance()->buildActivityProvider(ConnectedServicesVersionedDataProviderAlias::class);
    }

    /**
     * @throws Exception
     */
    public function testCanBuildConnectedServicesProvider(): void
    {
        $this->assertInstanceOf(
            ConnectedServicesVersionedDataProviderAlias::class,
            $this->prepareMockedInstance()->buildConnectedServicesProvider(
                ConnectedServicesVersionedDataProviderAlias::class
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testBuildConnectedServicesProviderThrowsForInvalidClass(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->prepareMockedInstance()->buildConnectedServicesProvider(ActivityVersionedDataProviderAlias::class);
    }
}
