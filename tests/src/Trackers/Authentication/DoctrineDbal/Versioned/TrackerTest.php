<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper
 * @uses \SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Helpers\HashHelper
 *
 * @psalm-suppress all
 */
class TrackerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ModuleConfiguration|ModuleConfiguration&\PHPUnit\Framework\MockObject\Stub
     */
    protected $moduleConfigurationStub;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface|LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Store|Store&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataStoreMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(ConnectionParameters::DBAL_SQLITE_MEMORY);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dataStoreMock = $this->createMock(Store::class);
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Tracker::class,
            new Tracker($this->moduleConfigurationStub, $this->loggerMock, $this->dataStoreMock)
        );

        $this->assertInstanceOf(
            Tracker::class,
            new Tracker($this->moduleConfigurationStub, $this->loggerMock)
        );

        $this->assertInstanceOf(
            Tracker::class,
            Tracker::build($this->moduleConfigurationStub, $this->loggerMock)
        );
    }

    public function testProcessCallsPersistOnDataStore(): void
    {
        $authenticationEventStub = $this->createStub(Event::class);

        $this->dataStoreMock->expects($this->once())
            ->method('persist')
            ->with($authenticationEventStub);

        /** @psalm-suppress PossiblyInvalidArgument */
        $tracker = new Tracker($this->moduleConfigurationStub, $this->loggerMock, $this->dataStoreMock);

        $tracker->process($authenticationEventStub);
    }

    public function testSetupDependsOnDataStore(): void
    {
        $this->dataStoreMock->expects($this->once())
            ->method('needsSetup')
            ->willReturn(true);

        $this->dataStoreMock->expects($this->once())
            ->method('runSetup');

        /** @psalm-suppress PossiblyInvalidArgument */
        $tracker = new Tracker($this->moduleConfigurationStub, $this->loggerMock, $this->dataStoreMock);

        $this->assertTrue($tracker->needsSetup());

        $tracker->runSetup();
    }

    public function testGetConnectedServiceProviders(): void
    {
        $connectedOrganizationsBagStub = $this->createStub(ConnectedServiceProvider\Bag::class);
        $this->dataStoreMock->expects($this->once())
            ->method('getConnectedOrganizations')
            ->willReturn($connectedOrganizationsBagStub);

        /** @psalm-suppress PossiblyInvalidArgument */
        $tracker = new Tracker($this->moduleConfigurationStub, $this->loggerMock, $this->dataStoreMock);

        $this->assertInstanceOf(
            ConnectedServiceProvider\Bag::class,
            $tracker->getConnectedServiceProviders('test')
        );
    }

    public function testGetActivity(): void
    {
        $activityBag = $this->createStub(Activity\Bag::class);
        $this->dataStoreMock->expects($this->once())
            ->method('getActivity')
            ->willReturn($activityBag);

        $tracker = new Tracker($this->moduleConfigurationStub, $this->loggerMock, $this->dataStoreMock);

        $this->assertInstanceOf(
            Activity\Bag::class,
            $tracker->getActivity('test')
        );
    }
}