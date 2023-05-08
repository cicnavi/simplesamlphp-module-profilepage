<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Providers\Builders;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\accounting\Data\Trackers\Activity\DoctrineDbal\VersionedDataTracker;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Trackers\Activity\DoctrineDbal\VersionedDataTracker
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 */
class DataProviderBuilderTest extends TestCase
{
    protected Stub $moduleConfigurationStub;

    protected Stub $loggerStub;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->loggerStub = $this->createStub(LoggerInterface::class);
        $this->helpersManager = new HelpersManager();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            DataProviderBuilder::class,
            new DataProviderBuilder(
                $this->moduleConfigurationStub,
                $this->loggerStub,
                $this->helpersManager
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCanBuildDataProvider(): void
    {
        $builder = new DataProviderBuilder(
            $this->moduleConfigurationStub,
            $this->loggerStub,
            $this->helpersManager
        );

        $this->assertInstanceOf(VersionedDataTracker::class, $builder->build(VersionedDataTracker::class));
    }

    public function testThrowsForInvalidClass(): void
    {
        $this->expectException(Exception::class);

        (new DataProviderBuilder(
            $this->moduleConfigurationStub,
            $this->loggerStub,
            $this->helpersManager
        ))->build('invalid');
    }
}
