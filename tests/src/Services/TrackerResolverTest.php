<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\DataProviderInterface;
use SimpleSAML\Module\accounting\Data\Trackers\Builders\DataTrackerBuilder;
use SimpleSAML\Module\accounting\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\TrackerResolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\TrackerResolver
 */
class TrackerResolverTest extends TestCase
{
    protected MockObject $moduleConfigurationMock;
    protected MockObject $loggerMock;
    protected MockObject $helpersManagerMock;
    protected MockObject $dataProviderBuilderMock;
    protected MockObject $dataTrackerBuilderMock;
    protected MockObject $dataProviderMock;
    protected MockObject $dataTrackerMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helpersManagerMock = $this->createMock(HelpersManager::class);
        $this->dataProviderBuilderMock = $this->createMock(DataProviderBuilder::class);
        $this->dataTrackerBuilderMock = $this->createMock(DataTrackerBuilder::class);

        $this->dataProviderMock = $this->createMock(DataProviderInterface::class);
        $this->dataTrackerMock = $this->createMock(DataTrackerInterface::class);
    }

    public function testCanConstruct(): void
    {
        $this->assertInstanceOf(
            TrackerResolver::class,
            new TrackerResolver(
                $this->moduleConfigurationMock,
                $this->loggerMock,
                $this->helpersManagerMock,
                $this->dataProviderBuilderMock,
                $this->dataTrackerBuilderMock
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testFromModuleConfiguration(): void
    {
        $this->moduleConfigurationMock->method('getProviderClasses')->willReturn(
            [DataProviderInterface::class]
        );
        $this->dataProviderMock->method('getTracker')->willReturn($this->dataTrackerMock);
        $this->dataProviderBuilderMock->method('build')->willReturn($this->dataProviderMock);
        $this->moduleConfigurationMock->method('getAdditionalTrackers')->willReturn(
            [DataTrackerInterface::class]
        );
        $this->dataTrackerBuilderMock->method('build')->willReturn($this->dataTrackerMock);

        $trackerResolver = new TrackerResolver(
            $this->moduleConfigurationMock,
            $this->loggerMock,
            $this->helpersManagerMock,
            $this->dataProviderBuilderMock,
            $this->dataTrackerBuilderMock
        );

        $this->assertCount(2, $trackerResolver->fromModuleConfiguration());
    }
}
