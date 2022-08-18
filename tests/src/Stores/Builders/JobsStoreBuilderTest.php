<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Builders;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreInterface;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder
 * @covers \SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder
 * @uses   \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Repository
 */
class JobsStoreBuilderTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected AbstractStoreBuilder $storeBuilder;
    protected JobsStoreBuilder $jobsStoreBuilder;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getStoreConnectionParameters')
            ->willReturn(['driver' => 'pdo_sqlite', 'memory' => true,]);

        /** @psalm-suppress InvalidArgument */
        $this->storeBuilder = new class ($this->moduleConfigurationStub) extends AbstractStoreBuilder {
        };

        /** @psalm-suppress InvalidArgument */
        $this->jobsStoreBuilder = new JobsStoreBuilder($this->moduleConfigurationStub);
    }

    public function testCanBuildStore(): void
    {
        $this->assertInstanceOf(StoreInterface::class, $this->storeBuilder->build(JobsStore::class));
    }

    public function testBuildThrowsForInvalidStore(): void
    {
        $this->expectException(StoreException::class);

        $this->storeBuilder->build('invalid-class');
    }

    public function testBuildThrowsForInvalidConfiguration(): void
    {
        $moduleConfiguration = $this->createStub(ModuleConfiguration::class);
        $moduleConfiguration->method('getStoreConnectionParameters')->willReturn(['invalid']);

        $storeBuilder = new class ($moduleConfiguration) extends AbstractStoreBuilder {
        };

        $this->expectException(StoreException::class);

        $storeBuilder->build(JobsStore::class);
    }

    public function testCanBuildJobsStore(): void
    {
        $this->assertInstanceOf(JobsStore::class, $this->jobsStoreBuilder->build(JobsStore::class));
    }

    public function testJobsStoreBuilderOnlyReturnsJobsStores(): void
    {
        $sampleStore = new class implements StoreInterface {
            public function needsSetUp(): bool
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

        $this->expectException(StoreException::class);

        $this->jobsStoreBuilder->build(get_class($sampleStore));
    }
}
