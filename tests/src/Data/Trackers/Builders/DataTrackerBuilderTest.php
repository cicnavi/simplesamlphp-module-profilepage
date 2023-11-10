<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Trackers\Builders;

use DateInterval;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Trackers\Builders\DataTrackerBuilder;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Trackers\Builders\DataTrackerBuilder
 * @uses \SimpleSAML\Module\profilepage\Helpers\InstanceBuilderUsingModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class DataTrackerBuilderTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $loggerMock;
    /**
     * @var Stub
     */
    protected $moduleConfigurationStub;

    protected DataTrackerInterface $trackerStub;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->helpersManager = new HelpersManager();

        $this->trackerStub = new class implements DataTrackerInterface {
            public static function build(
                ModuleConfiguration $moduleConfiguration,
                LoggerInterface $logger
            ): DataTrackerInterface {
                return new self();
            }

            public function process(Event $authenticationEvent): void
            {
            }

            public function needsSetup(): bool
            {
                return false;
            }

            public function runSetup(): void
            {
            }

            public function enforceDataRetentionPolicy(DateInterval $retentionPolicy): void
            {
            }
        };
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            DataTrackerBuilder::class,
            new DataTrackerBuilder(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                $this->helpersManager
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testCanBuildAuthenticationDataTracker(): void
    {
        $authenticationDataTrackerBuilder = new DataTrackerBuilder(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager
        );

        $trackerClass = $this->trackerStub::class;

        $this->assertInstanceOf($trackerClass, $authenticationDataTrackerBuilder->build($trackerClass));
    }

    public function testBuildThrowsForInvalidTrackerClass(): void
    {
        $authenticationDataTrackerBuilder = new DataTrackerBuilder(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager
        );

        $this->expectException(Exception::class);

        $authenticationDataTrackerBuilder->build('invalid');
    }
}
