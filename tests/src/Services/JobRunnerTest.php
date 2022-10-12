<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\JobRunner;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;

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
     * @var Stub|CacheInterface
     */
    protected $cacheStub;
    /**
     * @var Stub|JobRunner\State
     */
    protected $stateStub;
    /**
     * @var Stub|JobRunner\RateLimiter
     */
    protected $rateLimiter;
    /**
     * @var Stub|AuthenticationDataTrackerBuilder
     */
    protected $authenticationDataTrackerBuilder;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->sspConfigurationStub = $this->createStub(Configuration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->authenticationDataTrackerBuilder = $this->createStub(AuthenticationDataTrackerBuilder::class);
        $this->cacheStub = $this->createStub(CacheInterface::class);
        $this->stateStub = $this->createStub(JobRunner\State::class);
        $this->rateLimiter = $this->createStub(JobRunner\RateLimiter::class);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            JobRunner::class,
            new JobRunner(
                $this->moduleConfigurationStub,
                $this->sspConfigurationStub,
                $this->loggerMock,
                $this->authenticationDataTrackerBuilder,
                $this->cacheStub,
                $this->stateStub,
                $this->rateLimiter
            )
        );
    }
}