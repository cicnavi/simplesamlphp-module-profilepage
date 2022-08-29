<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Builders;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreInterface;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder
 * @covers \SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder
 * @uses   \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper
 */
class JobsStoreBuilderTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;
    protected JobsStoreBuilder $jobsStoreBuilder;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $this->moduleConfigurationStub->method('getJobsStoreClass')->willReturn(Store::class);

        $this->loggerStub = $this->createStub(LoggerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $this->jobsStoreBuilder = new JobsStoreBuilder($this->moduleConfigurationStub, $this->loggerStub);
    }

    public function testCanBuildJobsStore(): void
    {
        $this->assertInstanceOf(Store::class, $this->jobsStoreBuilder->build(Store::class));
    }

    public function testThrowsForInvalidStoreClass(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $invalidStore = new class {
        };

        /** @psalm-suppress InvalidArgument */
        $storeBuilder = new class ($moduleConfigurationStub, $this->loggerStub) extends AbstractStoreBuilder {
            public function build(string $class, string $connectionKey = null): StoreInterface
            {
                return $this->buildGeneric($class, [$connectionKey]);
            }
        };

        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        $storeBuilder->build(get_class($invalidStore));
    }

    public function testThrowsForInvalidJobsStoreClass(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        (new JobsStoreBuilder($moduleConfigurationStub, $this->loggerStub))->build('invalid');
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
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $moduleConfigurationStub->method('getJobsStoreClass')->willReturn(get_class($sampleStore));

        $this->expectException(StoreException::class);

        /** @psalm-suppress InvalidArgument */
        (new JobsStoreBuilder($moduleConfigurationStub, $this->loggerStub))->build(get_class($sampleStore));
    }
}