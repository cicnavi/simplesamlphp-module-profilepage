<?php

namespace SimpleSAML\Test\Module\accounting\Auth\Process;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Auth\Process\Accounting;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Auth\Process\Accounting
 * @uses   \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses   \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses   \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses   \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses   \SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses   \SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder
 * @uses   \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses   \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 */
class AccountingTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected \PHPUnit\Framework\MockObject\MockObject $loggerMock;
    protected array $filterConfig;
    protected \PHPUnit\Framework\MockObject\MockObject $jobsStoreBuilderMock;
    protected \PHPUnit\Framework\MockObject\MockObject $authenticationDataTrackerBuilderMock;
    protected \PHPUnit\Framework\MockObject\MockObject $jobsStoreMock;
    protected \PHPUnit\Framework\MockObject\MockObject $trackerMock;
    protected array $sampleState;

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Accounting::class,
            new Accounting(
                $this->filterConfig,
                null,
                $this->moduleConfigurationStub,
                $this->loggerMock,
            )
        );

        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Accounting::class,
            new Accounting(
                $this->filterConfig,
                null,
                $this->moduleConfigurationStub,
                $this->loggerMock,
                $this->jobsStoreBuilderMock,
                $this->authenticationDataTrackerBuilderMock
            )
        );
    }

    public function testCreatesJobOnAsynchronousAccountingType(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getJobsStoreClass')
            ->willReturn(Store::class);

        $this->jobsStoreMock->expects($this->once())
            ->method('enqueue')
            ->with($this->isInstanceOf(Event\Job::class));

        $this->jobsStoreBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->equalTo(Store::class))
            ->willReturn($this->jobsStoreMock);

        $this->authenticationDataTrackerBuilderMock
            ->expects($this->never())
            ->method('build');

        /** @psalm-suppress InvalidArgument */
        (new Accounting(
            $this->filterConfig,
            null,
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->jobsStoreBuilderMock,
            $this->authenticationDataTrackerBuilderMock
        ))->process($this->sampleState);
    }

    public function testAccountingRunsOnSynchronousType(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS);

        $this->moduleConfigurationStub->method('getDefaultDataTrackerAndProviderClass')
            ->willReturn(Tracker::class);
        $this->moduleConfigurationStub->method('getAdditionalTrackers')->willReturn([]);

        $this->jobsStoreBuilderMock->expects($this->never())
            ->method('build');

        $this->trackerMock
            ->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(Event::class));

        $this->authenticationDataTrackerBuilderMock
            ->expects($this->once())
            ->method('build')
            ->with($this->equalTo(Tracker::class))
            ->willReturn($this->trackerMock);

        /** @psalm-suppress InvalidArgument */
        (new Accounting(
            $this->filterConfig,
            null,
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->jobsStoreBuilderMock,
            $this->authenticationDataTrackerBuilderMock
        ))->process($this->sampleState);
    }

    public function testLogsErrorOnException(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willThrowException(new InvalidConfigurationException('test'));

        $this->loggerMock->expects($this->once())->method('error');

        /** @psalm-suppress InvalidArgument */
        (new Accounting(
            $this->filterConfig,
            null,
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->jobsStoreBuilderMock,
            $this->authenticationDataTrackerBuilderMock
        ))->process($this->sampleState);
    }

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->jobsStoreBuilderMock = $this->createMock(JobsStoreBuilder::class);
        $this->authenticationDataTrackerBuilderMock =
            $this->createMock(AuthenticationDataTrackerBuilder::class);

        $this->jobsStoreMock = $this->createMock(Store::class);
        $this->trackerMock = $this->createMock(Tracker::class);

        $this->sampleState = StateArrays::FULL;

        $this->filterConfig = [];
    }
}
