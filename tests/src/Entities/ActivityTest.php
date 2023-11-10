<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\profilepage\Entities\Activity;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\profilepage\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\profilepage\Entities\User;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Activity
 */
class ActivityTest extends TestCase
{
    /**
     * @var Stub|ServiceProviderInterface
     */
    protected $serviceProviderStub;
    /**
     * @var Stub|User
     */
    protected $userStub;
    protected DateTimeImmutable $happenedAt;
    protected string $clientIpAddress;
    protected string $authenticationProtocolDesignation;

    public function setUp(): void
    {
        $this->serviceProviderStub = $this->createStub(ServiceProviderInterface::class);
        $this->userStub = $this->createStub(User::class);
        $this->happenedAt = new DateTimeImmutable();
        $this->clientIpAddress = '123.123.123.123';
        $this->authenticationProtocolDesignation = Saml2::DESIGNATION;
    }

    public function testCanCreateInstance(): void
    {
        $activity = new Activity(
            $this->serviceProviderStub,
            $this->userStub,
            $this->happenedAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation
        );

        $this->assertSame($this->serviceProviderStub, $activity->getServiceProvider());
        $this->assertSame($this->userStub, $activity->getUser());
        $this->assertSame($this->happenedAt, $activity->getHappenedAt());
        $this->assertSame($this->clientIpAddress, $activity->getClientIpAddress());
        $this->assertSame($this->authenticationProtocolDesignation, $activity->getAuthenticationProtocolDesignation());
    }
}
