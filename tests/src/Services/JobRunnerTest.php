<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Helpers\DateTimeHelper;
use SimpleSAML\Module\accounting\Helpers\EnvironmentHelper;
use SimpleSAML\Module\accounting\Helpers\RandomHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\JobRunner;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;

/**
 * @covers \SimpleSAML\Module\accounting\Services\JobRunner
 *
 * @psalm-suppress all
 */
class JobRunnerTest extends TestCase
{
    /**
     * @var Stub|ModuleConfiguration
     */
    protected $moduleConfigurationStub;
    /**
     * @var Stub|Configuration
     */
    protected $sspConfigurationStub;
    /**
     * @var MockObject|LoggerInterface
     */
    protected $loggerMock;
    /**
     * @var MockObject|CacheInterface
     */
    protected $cacheMock;
    /**
     * @var Stub|JobRunner\State
     */
    protected $stateStub;
    /**
     * @var Stub|JobRunner\RateLimiter
     */
    protected $rateLimiterMock;
    /**
     * @var Stub|AuthenticationDataTrackerBuilder
     */
    protected $authenticationDataTrackerBuilderStub;
    /**
     * @var MockObject|AuthenticationDataTrackerInterface
     */
    protected $authenticationDataTrackerMock;
    /**
     * @var Stub|JobsStoreBuilder
     */
    protected $jobsStoreBuilderStub;
    /**
     * @var Stub|RandomHelper
     */
    protected $randomHelperStub;
    /**
     * @var Stub|EnvironmentHelper
     */
    protected $environmentHelperStub;
    /**
     * @var Stub|DateTimeHelper
     */
    protected $dateTimeHelperStub;
    /**
     * @var Stub|HelpersManager
     */
    protected $helpersManagerStub;
    /**
     * @var Stub|JobsStoreInterface
     */
    protected $jobsStoreMock;
    /**
     * @var Stub|JobInterface
     */
    protected $jobStub;
    /**
     * @var Stub|AbstractPayload
     */
    protected $payloadStub;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->sspConfigurationStub = $this->createStub(Configuration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->authenticationDataTrackerBuilderStub = $this->createStub(AuthenticationDataTrackerBuilder::class);
        $this->authenticationDataTrackerMock = $this->createMock(AuthenticationDataTrackerInterface::class);
        $this->jobsStoreBuilderStub = $this->createStub(JobsStoreBuilder::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->stateStub = $this->createStub(JobRunner\State::class);
        $this->rateLimiterMock = $this->createMock(JobRunner\RateLimiter::class);
        $this->randomHelperStub = $this->createStub(RandomHelper::class);
        $this->environmentHelperStub = $this->createStub(EnvironmentHelper::class);
        $this->dateTimeHelperStub = $this->createStub(DateTimeHelper::class);
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
        $this->jobsStoreMock = $this->createMock(JobsStoreInterface::class);
        $this->jobStub = $this->createStub(JobInterface::class);
        $this->payloadStub = $this->createStub(Event::class);
    }

    public function testCanCreateInstance(): void
    {
        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

        $this->assertInstanceOf(
            JobRunner::class,
            new JobRunner(
                $this->moduleConfigurationStub,
                $this->sspConfigurationStub,
                $this->loggerMock,
                $this->helpersManagerStub,
                $this->authenticationDataTrackerBuilderStub,
                $this->jobsStoreBuilderStub,
                $this->cacheMock,
                $this->stateStub,
                $this->rateLimiterMock
            )
        );
    }

    public function testPreRunValidationFailsForSameJobRunnerId(): void
    {
        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testPreRunValidationFailsForStaleState(): void
    {
        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testPreRunValidationPassesWhenStateIsNull(): void
    {
        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateRunConditionsFailsIfAnotherJobRunnerIsActive(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testAssumeTrueOnJobRunnerActivityIfThrown(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanLogCacheClearingError(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateRunConditionsSuccessIfStaleStateEncountered(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testShouldRunCheckFailsIfMaximumExecutionTimeIsReached(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->moduleConfigurationStub->method('getJobRunnerMaximumExecutionTime')
            ->willReturn(new \DateInterval('PT1S'));

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

        $this->cacheMock->method('get')->willReturn(null);

        $this->stateStub->method('getStartedAt')->willReturn(new \DateTimeImmutable('-2 seconds'));

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanUseIniSettingForMaximumExecutionTime(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->moduleConfigurationStub->method('getJobRunnerMaximumExecutionTime')
            ->willReturn(new \DateInterval('PT20S'));

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);
        $this->dateTimeHelperStub->method('convertDateIntervalToSeconds')->willReturn(20);
        $this->helpersManagerStub->method('getDateTimeHelper')->willReturn($this->dateTimeHelperStub);

        ini_set('max_execution_time', '10');

        $this->cacheMock->method('get')->willReturn(null);

        $this->stateStub->method('getStartedAt')->willReturn(new \DateTimeImmutable('-30 seconds'));

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testShouldRunCheckFailsIfMaximumNumberOfProcessedJobsIsReached(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateSelfStateFailsIfRunHasNotStartedButCachedStateExists(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateSelfStateFailsIfRunHasStartedButCachedStateDoesNotExist(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateSelfStateFailsIfRunHasStartedButDifferentJobRunnerIdEncountered(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            ->with($this->stringContains('Current job runner ID differs from the ID in the cached state.'));

        $jobRunner = new JobRunner(
            $this->moduleConfigurationStub,
            $this->sspConfigurationStub,
            $this->loggerMock,
            $this->helpersManagerStub,
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateSelfStateFailsIfRunHasStartedButStaleCachedStateEncountered(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testValidateSelfStateFailsIfRunHasStartedButGracefulInterruptIsInitiated(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanDoBackoffPauseIfNoJobsInCli(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(true);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanBreakImmediatelyIfNoJobsInWeb(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanProcessJob(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getDefaultDataTrackerAndProviderClass')
            ->willReturn('mock');

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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

        $this->authenticationDataTrackerMock->expects($this->once())
            ->method('process');
        $this->authenticationDataTrackerBuilderStub->method('build')
            ->willReturn($this->authenticationDataTrackerMock);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanLogCacheUpdateError(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getDefaultDataTrackerAndProviderClass')
            ->willReturn('mock');

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanPauseProcessingBasedOnConfiguration(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getDefaultDataTrackerAndProviderClass')
            ->willReturn('mock');
        $this->moduleConfigurationStub->method('getJobRunnerShouldPauseAfterNumberOfJobsProcessed')
            ->willReturn(0);

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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

        $this->authenticationDataTrackerMock->expects($this->exactly(2))
            ->method('process');
        $this->authenticationDataTrackerBuilderStub->method('build')
            ->willReturn($this->authenticationDataTrackerMock);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testCanMarkFailedJobOnError(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getDefaultDataTrackerAndProviderClass')
            ->willReturn('mock');

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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

        $this->authenticationDataTrackerMock->expects($this->once())
            ->method('process')
            ->willThrowException(new Exception('test'));
        $this->authenticationDataTrackerBuilderStub->method('build')
            ->willReturn($this->authenticationDataTrackerMock);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }

    public function testThrowsOnAlreadyInitializedState(): void
    {
        $this->moduleConfigurationStub->method('getAccountingProcessingType')
            ->willReturn(ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS);
        $this->moduleConfigurationStub->method('getDefaultDataTrackerAndProviderClass')
            ->willReturn('mock');

        $this->randomHelperStub->method('getRandomInt')->willReturn(123);
        $this->helpersManagerStub->method('getRandomHelper')->willReturn($this->randomHelperStub);
        $this->environmentHelperStub->method('isCli')->willReturn(false);
        $this->helpersManagerStub->method('getEnvironmentHelper')->willReturn($this->environmentHelperStub);

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
            $this->authenticationDataTrackerBuilderStub,
            $this->jobsStoreBuilderStub,
            $this->cacheMock,
            $this->stateStub,
            $this->rateLimiterMock
        );

        $jobRunner->run();
    }
}
