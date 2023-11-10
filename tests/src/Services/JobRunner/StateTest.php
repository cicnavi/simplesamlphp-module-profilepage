<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services\JobRunner;

use DateInterval;
use DateTimeImmutable;
use SimpleSAML\Module\profilepage\Services\JobRunner\State;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\JobRunner\State
 */
class StateTest extends TestCase
{
    protected int $jobRunnerId;

    protected function setUp(): void
    {
        $this->jobRunnerId = 1;
    }

    public function testCanCreateInstance(): void
    {
        $startedAt = $updatedAt = new DateTimeImmutable();

        $state = new State($this->jobRunnerId);
        $this->assertInstanceOf(State::class, $state);
        $this->assertSame($this->jobRunnerId, $state->getJobRunnerId());

        $state = new State($this->jobRunnerId, $startedAt, null);
        $this->assertInstanceOf(State::class, $state);

        $state = new State($this->jobRunnerId, $startedAt, $updatedAt);
        $this->assertInstanceOf(State::class, $state);

        $state = new State($this->jobRunnerId, $startedAt, $updatedAt, 1000);
        $this->assertInstanceOf(State::class, $state);
    }

    public function testCanWorkWithTimestamps(): void
    {
        $startedAt = $updatedAt = $endedAt = new DateTimeImmutable();

        $state = new State($this->jobRunnerId);
        $this->assertNull($state->getStartedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $state->getUpdatedAt());
        $this->assertNull($state->getEndedAt());

        $this->assertTrue($state->setStartedAt($startedAt));
        $this->assertTrue($state->hasRunStarted());
        $state->setUpdatedAt($updatedAt);
        $this->assertTrue($state->setEndedAt($endedAt));

        $this->assertSame($startedAt, $state->getStartedAt());
        $this->assertSame($updatedAt, $state->getUpdatedAt());
        $this->assertSame($endedAt, $state->getEndedAt());

        $this->assertFalse($state->setStartedAt($startedAt));
        $this->assertFalse($state->setEndedAt($endedAt));
    }

    public function testCanCountProcessedJobs(): void
    {
        $state = new State($this->jobRunnerId);

        $this->assertSame(0, $state->getTotalJobsProcessed());
        $state->incrementSuccessfulJobsProcessed();

        $this->assertSame(1, $state->getTotalJobsProcessed());
        $this->assertSame(1, $state->getSuccessfulJobsProcessed());
        $this->assertSame(0, $state->getFailedJobsProcessed());

        $state->incrementFailedJobsProcessed();

        $this->assertSame(2, $state->getTotalJobsProcessed());
        $this->assertSame(1, $state->getSuccessfulJobsProcessed());
        $this->assertSame(1, $state->getFailedJobsProcessed());
    }

    public function testCanCheckIfStateIsStale(): void
    {
        $state = new State($this->jobRunnerId);
        $freshnessDuration = new DateInterval('PT5M');

        $this->assertFalse($state->isStale($freshnessDuration));

        $dateTimeInHistory = new DateTimeImmutable('-9 minutes');
        $state->setUpdatedAt($dateTimeInHistory);

        $this->assertTrue($state->isStale($freshnessDuration));
    }

    public function testCanWorkWithStatusMessages(): void
    {
        $state = new State($this->jobRunnerId, null, null, 2);
        $this->assertEmpty($state->getStatusMessages());
        $this->assertNull($state->getLastStatusMessage());

        $state->addStatusMessage('test');
        $this->assertSame(1, count($state->getStatusMessages()));
        $this->assertSame('test', $state->getLastStatusMessage());
        $state->addStatusMessage('test2');
        $this->assertSame('test2', $state->getLastStatusMessage());
        $this->assertSame(2, count($state->getStatusMessages()));
        $state->addStatusMessage('test3');
        $this->assertSame('test3', $state->getLastStatusMessage());
        $this->assertSame(2, count($state->getStatusMessages()));
        $this->assertSame('test3', $state->getLastStatusMessage());
    }

    public function testCanSetGracefulInterruptInitiatedFlag(): void
    {
        $state = new State($this->jobRunnerId);

        $this->assertFalse($state->getIsGracefulInterruptInitiated());
        $state->setIsGracefulInterruptInitiated(true);
        $this->assertTrue($state->getIsGracefulInterruptInitiated());
    }
}
