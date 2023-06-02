<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Builders;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Data\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\StoreInterface;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Builders\JobsStoreBuilder
 * @uses   \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
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

        $storeBuilder->build(get_class($invalidStore));
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
        $moduleConfigurationStub->method('getJobsStoreClass')->willReturn(get_class($sampleStore));

        $this->expectException(StoreException::class);

        (new JobsStoreBuilder($moduleConfigurationStub, $this->loggerStub, $this->helpersManager))
            ->build(get_class($sampleStore));
    }
}
