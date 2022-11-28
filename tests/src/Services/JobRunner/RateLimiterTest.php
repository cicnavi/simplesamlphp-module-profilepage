<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services\JobRunner;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Services\JobRunner\RateLimiter;

/**
 * @covers \SimpleSAML\Module\accounting\Services\JobRunner\RateLimiter
 * @uses \SimpleSAML\Module\accounting\Helpers\DateTimeHelper
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 */
class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RateLimiter::class, new RateLimiter());
    }

    public function testCanDoPause(): void
    {
        $rateLimiter = new RateLimiter();
        $startTimeInSeconds = (new DateTimeImmutable())->getTimestamp();
        $rateLimiter->doPause();
        $endTimeInSeconds = (new DateTimeImmutable())->getTimestamp();

        $this->assertTrue(($endTimeInSeconds - $startTimeInSeconds) >= 1);
    }

    public function testCanSetMaxPause(): void
    {
        $rateLimiter = new RateLimiter(new DateInterval('PT1S'));
        $this->assertSame(1, $rateLimiter->getMaxPauseInSeconds());
        $splitSecondInterval = DateInterval::createFromDateString('10000 microsecond'); // 10 milliseconds
        $rateLimiter = new RateLimiter($splitSecondInterval);
        $this->assertSame(1, $rateLimiter->getMaxPauseInSeconds());
        $rateLimiter->doPause();
    }

    public function testCanDoBackoffPause(): void
    {
        $rateLimiter = new RateLimiter();
        $startTimeInSeconds = (new DateTimeImmutable())->getTimestamp();
        $rateLimiter->doBackoffPause();
        $endTimeInSeconds = (new DateTimeImmutable())->getTimestamp();
        $this->assertTrue(($endTimeInSeconds - $startTimeInSeconds) >= 1);
        $this->assertTrue($rateLimiter->getCurrentBackoffPauseInSeconds() > 1);
        $rateLimiter->resetBackoffPause();
        $this->assertTrue($rateLimiter->getCurrentBackoffPauseInSeconds() === 1);
    }

    public function testCanSetMaxBackoffPause(): void
    {
        $rateLimiter = new RateLimiter(null, new DateInterval('PT1S'));
        $this->assertSame(1, $rateLimiter->getMaxBackoffPauseInSeconds());
        $splitSecondInterval = DateInterval::createFromDateString('10000 microsecond'); // 10 milliseconds
        $rateLimiter = new RateLimiter(null, $splitSecondInterval);
        $this->assertSame(1, $rateLimiter->getMaxBackoffPauseInSeconds());
        $rateLimiter->doBackoffPause();
    }
}
