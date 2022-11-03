<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Trackers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 *
 * @psalm-suppress all
 */
class AuthenticationDataTrackerBuilderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface|LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerMock;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ModuleConfiguration|ModuleConfiguration&\PHPUnit\Framework\MockObject\Stub
     */
    protected $moduleConfigurationStub;

    protected AuthenticationDataTrackerInterface $trackerStub;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->helpersManager = new HelpersManager();

        $this->trackerStub = new class implements AuthenticationDataTrackerInterface {
            public static function build(
                ModuleConfiguration $moduleConfiguration,
                LoggerInterface $logger
            ): AuthenticationDataTrackerInterface {
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
        };
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            AuthenticationDataTrackerBuilder::class,
            new AuthenticationDataTrackerBuilder(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                $this->helpersManager
            )
        );
    }

    public function testCanBuildAuthenticationDataTracker(): void
    {
        $authenticationDataTrackerBuilder = new AuthenticationDataTrackerBuilder(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager
        );

        $trackerClass = get_class($this->trackerStub);

        $this->assertInstanceOf($trackerClass, $authenticationDataTrackerBuilder->build($trackerClass));
    }

    public function testBuildThrowsForInvalidTrackerClass(): void
    {
        $authenticationDataTrackerBuilder = new AuthenticationDataTrackerBuilder(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager
        );

        $this->expectException(Exception::class);

        $authenticationDataTrackerBuilder->build('invalid');
    }
}
