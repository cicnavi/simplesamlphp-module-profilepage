<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Builders;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
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
 */
class JobsStoreBuilderTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected JobsStoreBuilder $jobsStoreBuilder;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->moduleConfigurationStub->method('getJobsStoreClass')->willReturn(Store::class);

        /** @psalm-suppress InvalidArgument */
        $this->jobsStoreBuilder = new JobsStoreBuilder($this->moduleConfigurationStub);
    }

    public function testCanBuildJobsStore(): void
    {
        $this->assertInstanceOf(Store::class, $this->jobsStoreBuilder->build());
    }

    public function testThrowsForInvalidStoreClass(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $moduleConfigurationStub->method('getJobsStoreClass')->willReturn('invalid');

        $this->expectException(StoreException::class);

        (new JobsStoreBuilder($moduleConfigurationStub))->build();
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

            public static function build(ModuleConfiguration $moduleConfiguration): StoreInterface
            {
                return new self();
            }
        };

        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);
        $moduleConfigurationStub->method('getJobsStoreClass')->willReturn(get_class($sampleStore));

        $this->expectException(StoreException::class);

        (new JobsStoreBuilder($moduleConfigurationStub))->build();
    }
}
