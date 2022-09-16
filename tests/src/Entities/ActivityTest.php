<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\Activity;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\ServiceProvider;
use SimpleSAML\Module\accounting\Entities\User;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Activity
 */
class ActivityTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ServiceProvider|ServiceProvider&\PHPUnit\Framework\MockObject\Stub
     */
    protected $serviceProviderStub;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|User|User&\PHPUnit\Framework\MockObject\Stub
     */
    protected $userStub;
    protected \DateTimeImmutable $happenedAt;

    public function setUp(): void
    {
        $this->serviceProviderStub = $this->createStub(ServiceProvider::class);
        $this->userStub = $this->createStub(User::class);
        $this->happenedAt = new \DateTimeImmutable();
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $activity = new Activity(
            $this->serviceProviderStub,
            $this->userStub,
            $this->happenedAt
        );

        $this->assertSame($this->serviceProviderStub, $activity->getServiceProvider());
        $this->assertSame($this->userStub, $activity->getUser());
        $this->assertSame($this->happenedAt, $activity->getHappenedAt());
    }
}