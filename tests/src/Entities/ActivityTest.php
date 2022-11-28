<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
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
     * @var Stub|ServiceProvider
     */
    protected $serviceProviderStub;
    /**
     * @var Stub|User
     */
    protected $userStub;
    protected DateTimeImmutable $happenedAt;
    protected string $clientIpAddress;

    public function setUp(): void
    {
        $this->serviceProviderStub = $this->createStub(ServiceProvider::class);
        $this->userStub = $this->createStub(User::class);
        $this->happenedAt = new DateTimeImmutable();
        $this->clientIpAddress = '123.123.123.123';
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $activity = new Activity(
            $this->serviceProviderStub,
            $this->userStub,
            $this->happenedAt,
            $this->clientIpAddress
        );

        $this->assertSame($this->serviceProviderStub, $activity->getServiceProvider());
        $this->assertSame($this->userStub, $activity->getUser());
        $this->assertSame($this->happenedAt, $activity->getHappenedAt());
        $this->assertSame($this->clientIpAddress, $activity->getClientIpAddress());
    }
}
