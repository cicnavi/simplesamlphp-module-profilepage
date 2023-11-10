<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Builders;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\profilepage\Data\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\profilepage\Data\Stores\Interfaces\StoreInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Builders\JobsStoreBuilder
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
 */
class JobsStoreBuilderTest extends TestCase
{
    protected Stub $moduleConfigurationStub;
    protected Stub $loggerStub;
    protected JobsStoreBuilder $jobsStoreBuilder;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->moduleConfigurationStub->method('getJobsStoreClass')->willReturn(Store::class);
        $this->moduleConfigurationStub->method('getSerializerClass')->willReturn(PhpSerializer::class);

        $this->loggerStub = $this->createStub(LoggerInterface::class);

        $this->helpersManager = new HelpersManager();

        $this->jobsStoreBuilder = new JobsStoreBuilder(
            $this->moduleConfigurationStub,
            $this->loggerStub,
            $this->helpersManager
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanBuildJobsStore(): void
    {
        $this->assertInstanceOf(Store::class, $this->jobsStoreBuilder->build(Store::class));
    }

    public function testThrowsForInvalidStoreClass(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $invalidStore = new class {
        };

        $storeBuilder = new class (
            $moduleConfigurationStub,
            $this->loggerStub,
            $this->helpersManager
        ) extends AbstractStoreBuilder {
            public function build(
                string $class,
                string $connectionKey = null,
                string $connectionType = ModuleConfiguration\ConnectionType::MASTER
            ): StoreInterface {
                return $this->buildGeneric($class, [$connectionKey, $connectionType]);
            }
        };

        $this->expectException(StoreException::class);

        $storeBuilder->build($invalidStore::class);
    }

    public function testThrowsForInvalidJobsStoreClass(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->expectException(StoreException::class);

        (new JobsStoreBuilder($moduleConfigurationStub, $this->loggerStub, $this->helpersManager))
            ->build('invalid');
    }

    public function testJobsStoreBuilderOnlyReturnsJobsStores(): void
    {
        $sampleStore = new class implements StoreInterface {
            public function needsSetup(): bool
            {
                return false;
            }

            public function runSetup(): void
            {
            }

            public static function build(
                ModuleConfiguration $moduleConfiguration,
                LoggerInterface $logger,
                string $connectionKey = null
            ): StoreInterface {
                return new self();
            }
        };

        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $moduleConfigurationStub->method('getJobsStoreClass')->willReturn($sampleStore::class);

        $this->expectException(StoreException::class);

        (new JobsStoreBuilder($moduleConfigurationStub, $this->loggerStub, $this->helpersManager))
            ->build($sampleStore::class);
    }
}
