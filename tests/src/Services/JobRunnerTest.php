<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Data\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Helpers\DateTime;
use SimpleSAML\Module\accounting\Helpers\Environment;
use SimpleSAML\Module\accounting\Helpers\Random;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\JobRunner;
use SimpleSAML\Module\accounting\Services\TrackerResolver;

/**
 * @covers \SimpleSAML\Module\accounting\Services\JobRunner
 */
class JobRunnerTest extends TestCase
{
    /**
     * @var Stub
     */
    protected $moduleConfigurationStub;
    /**
     * @var Stub
     */
    protected $sspConfigurationStub;
    /**
     * @var MockObject
     */
    protected $loggerMock;
    /**
     * @var MockObject
     */
    protected $cacheMock;
    /**
     * @var Stub
     */
    protected $stateStub;
    /**
     * @var Stub
     */
    protected $rateLimiterMock;
    /**
     * @var MockObject
     */
    protected $trackerResolverMock;
    /**
     * @var MockObject
     */
    protected $dataTrackerMock;
    /**
     * @var Stub
     */
    protected $jobsStoreBuilderStub;
    /**
     * @var Stub
     */
    protected $randomHelperStub;
    /**
     * @var Stub
     */
    protected $environmentHelperStub;
    /**
     * @var Stub
     */
    protected $dateTimeHelperStub;
    /**
     * @var Stub
     */
    protected $helpersManagerStub;
    /**
     * @var Stub
     */
    protected $jobsStoreMock;
    /**
     * @var Stub
     */
    protected $jobStub;
    /**
     * @var Stub
     */
    protected $payloadStub;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->sspConfigurationStub = $this->createStub(Configuration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->trackerResolverMock = $this->createMock(TrackerResolver::class);
        $this->dataTrackerMock = $this->createMock(DataTrackerInterface::class);
        $this->jobsStoreBuilderStub = $this->createStub(JobsStoreBuilder::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->stateStub = $this->createStub(JobRunner\State::class);
        $this->rateLimiterMock = $this->createMock(JobRunner\RateLimiter::class);
        $this->randomHelperStub = $this->createStub(Random::class);
        $this->environmentHelperStub = $this->createStub(Environment::class);
        $this->dateTimeHelperStub = $this->createStub(DateTime::class);
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
        $this->jobsStoreMock = $this->createMock(JobsStoreInterface::class);
        $this->jobStub = $this->createStub(JobInterface::class);
        $this->payloadStub = $this->createStub(Event::class);
    }

    /**
     * @throws Exception
     */
    public function testCanCreateInstance(): void
    {
        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->assertInstanceOf(
            JobRunner::class,
            new JobRunner(
                $this->moduleConfigurationStub,
                $this->sspConfigurationStub,
                $this->loggerMock,
                $this->helpersManagerStub,
                $this->trackerResolverMock,
                $this->jobsStoreBuilderStub,
                $this->cacheMock,
                $this->stateStub,
                $this->rateLimiterMock
            )
        );
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testPreRunValidationFailsForSameJobRunnerId(): void
    {
        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->cacheMock->method('get')->willReturn($this->stateStub);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('error')
            ->with('Job runner ID in cached state same as new ID.');
        $this->loggerMock->expects($this->atLeast(2))->method('warning')
            ->withConsecutive(
                [$this->stringContains('Pre-run state validation failed. Clearing cached state and continuing.')],
                [$this->stringContains('Job runner called, however accounting mode is not')]
            );

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testPreRunValidationFailsForStaleState(): void
    {
        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(321);
        $this->stateStub->method('isStale')->willReturn(true);
        $this->cacheMock->method('get')->willReturn($this->stateStub);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(3))->method('warning')
            ->withConsecutive(
                [$this->stringContains('Stale state encountered.')],
                [$this->stringContains('Pre-run state validation failed. Clearing cached state and continuing.')],
                [$this->stringContains('Job runner called, however accounting mode is not')]
            );

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testPreRunValidationPassesWhenStateIsNull(): void
    {
        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->cacheMock->method('get')->willReturn(null);

        $this->cacheMock->expects($this->never())->method('delete');

        $this->loggerMock->expects($this->atLeast(1))->method('warning')
            ->withConsecutive(
                [$this->stringContains('Job runner called, however accounting mode is not')]
            );

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateRunConditionsFailsIfAnotherJobRunnerIsActive(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(321);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->cacheMock->method('get')->willReturn($this->stateStub);

        $this->cacheMock->expects($this->never())->method('delete');

        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('Another job runner is active.'));
        $this->loggerMock->expects($this->once())->method('info')
            ->with($this->stringContains('Run conditions are not met, stopping.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testAssumeTrueOnJobRunnerActivityIfThrown(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(321);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            $this->stateStub,
            $this->throwException(new Exception('test'))
        );

        $this->cacheMock->expects($this->never())->method('delete');

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Error checking if another job runner is active.'));
        $this->loggerMock->expects($this->once())->method('info')
            ->with($this->stringContains('Run conditions are not met, stopping.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     */
    public function testCanLogCacheClearingError(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(321);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->cacheMock->method('get')->willThrowException(new Exception('test'));
        $this->cacheMock->method('delete')->willThrowException(new Exception('test'));

        $this->expectException(Exception::class);

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Error clearing job runner cache.'));
        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateRunConditionsSuccessIfStaleStateEncountered(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(321);
        $this->stateStub->method('isStale')->willReturn(true);
        $this->cacheMock->method('get')
            ->willReturnOnConsecutiveCalls(
                null,
                $this->stateStub
            );

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Assuming no job runner is active.'));
        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('Run conditions validated.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testShouldRunCheckFailsIfMaximumExecutionTimeIsReached(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->moduleConfigurationStub->method('getJobRunnerMaximumExecutionTime')
            ->willReturn(new DateInterval('PT1S'));

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->cacheMock->method('get')->willReturn(null);

        $this->stateStub->method('getStartedAt')->willReturn(new DateTimeImmutable('-2 seconds'));

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(2))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Run conditions validated.')],
                [$this->stringContains('Maximum job runner execution time reached.')]
            );

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanUseIniSettingForMaximumExecutionTime(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->moduleConfigurationStub->method('getJobRunnerMaximumExecutionTime')
            ->willReturn(new DateInterval('PT20S'));

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);
        $this->dateTimeHelperStub->method('convertDateIntervalToSeconds')->willReturn(20);
        $this->helpersManagerStub->method('getDateTime')->willReturn($this->dateTimeHelperStub);

        ini_set('max_execution_time', '10');

        $this->cacheMock->method('get')->willReturn(null);

        $this->stateStub->method('getStartedAt')->willReturn(new DateTimeImmutable('-30 seconds'));

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(3))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Using maximum execution time from INI setting since it is shorter.')],
                [$this->stringContains('Run conditions validated.')],
                [$this->stringContains('Maximum job runner execution time reached.')]
            );

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testShouldRunCheckFailsIfMaximumNumberOfProcessedJobsIsReached(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->cacheMock->method('get')->willReturn(null);

        $this->stateStub->method('getTotalJobsProcessed')->willReturn(PHP_INT_MAX);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(2))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Run conditions validated.')],
                [$this->stringContains('Maximum number of processed jobs reached.')]
            );

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateSelfStateFailsIfRunHasNotStartedButCachedStateExists(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(false);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('cached state has already been initialized.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateSelfStateFailsIfRunHasStartedButCachedStateDoesNotExist(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            null,
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('cached state has not been initialized.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateSelfStateFailsIfRunHasStartedButDifferentJobRunnerIdEncountered(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(321);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('CurrentDataProvider job runner ID differs from the ID in the cached state.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateSelfStateFailsIfRunHasStartedButStaleCachedStateEncountered(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Job runner cached state is stale'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testValidateSelfStateFailsIfRunHasStartedButGracefulInterruptIsInitiated(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')->willReturn(true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Graceful job processing interrupt initiated'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanDoBackoffPauseIfNoJobsInCli(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(true);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(2))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Run conditions validated.')],
                [$this->stringContains('Doing a backoff pause')]
            );

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Graceful job processing interrupt initiated'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanBreakImmediatelyIfNoJobsInWeb(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('Run conditions validated'));

        $this->loggerMock->expects($this->never())->method('warning')
            ->with($this->stringContains('Graceful job processing interrupt initiated'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanProcessJob(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getProviderClasses')
            ->willReturn(['mocks']);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->rateLimiterMock->expects($this->once())->method('resetBackoffPause');

        $this->dataTrackerMock->expects($this->once())->method('process');
        $this->trackerResolverMock->method('fromModuleConfiguration')->willReturn([$this->dataTrackerMock]);

        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobsStoreMock->method('dequeue')->willReturn($this->jobStub);
        $this->jobsStoreBuilderStub->method('build')->willReturn($this->jobsStoreMock);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(2))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Run conditions validated.')],
                [$this->stringContains('Successfully processed job with ID')]
            );

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Graceful job processing interrupt initiated'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     */
    public function testCanLogCacheUpdateError(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getProviderClasses')
            ->willReturn(['mocks']);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheMock->method('set')->willThrowException(new Exception('test'));

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobsStoreMock->method('dequeue')->willReturn($this->jobStub);
        $this->jobsStoreBuilderStub->method('build')->willReturn($this->jobsStoreMock);

        $this->loggerMock->expects($this->atLeast(1))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Run conditions validated.')],
            );

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Error setting job runner state'));

        $this->expectException(Exception::class);

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanPauseProcessingBasedOnConfiguration(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getProviderClasses')
            ->willReturn(['mocks']);
        $this->moduleConfigurationStub->method('getJobRunnerShouldPauseAfterNumberOfJobsProcessed')
            ->willReturn(0);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, false, true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub,
            $this->stateStub,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->rateLimiterMock->expects($this->exactly(2))->method('resetBackoffPause');
        $this->rateLimiterMock->expects($this->once())->method('doPause');

        $this->dataTrackerMock->expects($this->exactly(2))
            ->method('process');
        $this->trackerResolverMock->method('fromModuleConfiguration')->willReturn([$this->dataTrackerMock]);

        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobsStoreMock->method('dequeue')->willReturn($this->jobStub);
        $this->jobsStoreBuilderStub->method('build')->willReturn($this->jobsStoreMock);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->atLeast(3))->method('debug')
            ->withConsecutive(
                [$this->stringContains('Run conditions validated.')],
                [$this->stringContains('Successfully processed job with ID')],
                [$this->stringContains('Successfully processed job with ID')]
            );

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Graceful job processing interrupt initiated'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     * @throws Exception
     */
    public function testCanMarkFailedJobOnError(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getProviderClasses')
            ->willReturn(['mocks']);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            null,
            $this->stateStub,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->rateLimiterMock->expects($this->once())->method('resetBackoffPause');

        $this->dataTrackerMock->expects($this->once())
            ->method('process')
            ->willThrowException(new Exception('test'));
        $this->trackerResolverMock->method('fromModuleConfiguration')->willReturn([$this->dataTrackerMock]);

        $this->jobStub->method('getPayload')->willReturn($this->payloadStub);
        $this->jobsStoreMock->method('dequeue')->willReturn($this->jobStub);
        $this->jobsStoreMock->expects($this->once())->method('markFailedJob')->with($this->jobStub);
        $this->jobsStoreBuilderStub->method('build')->willReturn($this->jobsStoreMock);

        $this->cacheMock->expects($this->once())->method('delete');

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Error while processing jobs.'));

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('Graceful job processing interrupt initiated'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    /**
     * @throws StoreException
     */
    public function testThrowsOnAlreadyInitializedState(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getProviderClasses')
            ->willReturn(['mocks']);

        $this->randomHelperStub->method('getInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandom')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironment')->willReturn($this->environmentHelperStub);

        $this->stateStub->method('getJobRunnerId')->willReturn(123);
        $this->stateStub->method('isStale')->willReturn(false);
        $this->stateStub->method('getIsGracefulInterruptInitiated')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheMock->method('get')->willReturnOnConsecutiveCalls(
            null,
            null,
            $this->stateStub
        );

        $this->stateStub->method('hasRunStarted')->willReturn(true);

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Job runner state already initialized'));
        $this->expectException(Exception::class);

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->trackerResolverMock,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }
}
