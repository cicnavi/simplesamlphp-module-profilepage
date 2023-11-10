<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Auth\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Auth\Process\Accounting;
use SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider;
use SimpleSAML\Module\profilepage\Data\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\profilepage\Data\Trackers\Activity\DoctrineDbal\VersionedDataTracker;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\TrackerResolver;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Auth\Process\Accounting
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Helpers\AuthenticationEventStateResolver
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\profilepage\Data\Trackers\Builders\DataTrackerBuilder
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Providers\Builders\DataProviderBuilder
 * @uses \SimpleSAML\Module\profilepage\Services\TrackerResolver
 */
class AccountingTest extends TestCase
{
    protected Stub $moduleConfigurationStub;
    protected MockObject $loggerMock;
    protected array $filterConfig;
    protected MockObject $jobsStoreBuilderMock;
    protected MockObject $jobsStoreMock;
    protected MockObject $trackerMock;
    protected array $sampleState;
    protected HelpersManager $helpersManager;
    /**
     * @var MockObject
     */
    protected $trackerResolver;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->jobsStoreBuilderMock = $this->createMock(JobsStoreBuilder::class);

        $this->jobsStoreMock = $this->createMock(Store::class);
        $this->trackerMock = $this->createMock(VersionedDataTracker::class);

        $this->sampleState = StateArrays::SAML2_FULL;

        $this->filterConfig = [];

        $this->helpersManager = new HelpersManager();
        $this->trackerResolver = $this->createMock(TrackerResolver::class);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Accounting::class,
            new Accounting(
                $this->filterConfig,
                null,
                $this->moduleConfigurationStub,
                $this->loggerMock,
            )
        );

        $this->assertInstanceOf(
            Accounting::class,
            new Accounting(
                $this->filterConfig,
                null,
                $this->moduleConfigurationStub,
                $this->loggerMock,
                $this->helpersManager,
                $this->jobsStoreBuilderMock,
                $this->trackerResolver
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

        $this->trackerResolver
            ->expects($this->never())
            ->method('fromModuleConfiguration');

        (new Accounting(
            $this->filterConfig,
            null,
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager,
            $this->jobsStoreBuilderMock,
            $this->trackerResolver
        ))->process($this->sampleState);
    }

    public function testAccountingRunsOnSynchronousType(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS);

        $this->moduleConfigurationStub->method('getProviderClasses')
            ->willReturn([VersionedDataProvider::class]);
        $this->moduleConfigurationStub->method('getAdditionalTrackers')->willReturn([]);

        $this->jobsStoreBuilderMock->expects($this->never())
            ->method('build');

        $this->trackerMock
            ->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(Event::class));

        $this->trackerResolver
            ->expects($this->once())
            ->method('fromModuleConfiguration')
            ->willReturn([$this->trackerMock]);

        (new Accounting(
            $this->filterConfig,
            null,
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager,
            $this->jobsStoreBuilderMock,
            $this->trackerResolver
        ))->process($this->sampleState);
    }

    public function testLogsErrorOnException(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willThrowException(new InvalidConfigurationException('test'));

        $this->loggerMock->expects($this->once())->method('error');

        (new Accounting(
            $this->filterConfig,
            null,
            $this->moduleConfigurationStub,
            $this->loggerMock,
            $this->helpersManager,
            $this->jobsStoreBuilderMock,
            $this->trackerResolver
        ))->process($this->sampleState);
    }
}
