<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\DataStoreBuilder;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 */
class DataStoreBuilderTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;
    protected DataStoreBuilder $dataStoreBuilder;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->loggerStub = $this->createStub(LoggerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $this->dataStoreBuilder = new DataStoreBuilder($this->moduleConfigurationStub, $this->loggerStub);
    }

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