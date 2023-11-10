<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\profilepage\Entities\ConnectedService;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\profilepage\Entities\User;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\ConnectedService
 */
class ConnectedServiceTest extends TestCase
{
    /**
     * @var Stub
     */
    protected $serviceProviderStub;
    /**
     * @var Stub
     */
    protected $userStub;
    protected DateTimeImmutable $dateTime;
    protected int $numberOfAuthentications;

    public function setUp(): void
    {
        $this->serviceProviderStub = $this->createStub(ServiceProviderInterface::class);
        $this->userStub = $this->createStub(User::class);
        $this->dateTime = new DateTimeImmutable();
        $this->numberOfAuthentications = 1;
    }

    public function testCanCreateInstance(): void
    {
        $connectedServiceProvider = new ConnectedService(
            $this->serviceProviderStub,
            $this->numberOfAuthentications,
            $this->dateTime,
            $this->dateTime,
            $this->userStub
        );

        $this->assertSame($this->serviceProviderStub, $connectedServiceProvider->getServiceProvider());
        $this->assertSame($this->numberOfAuthentications, $connectedServiceProvider->getNumberOfAuthentications());
        $this->assertSame($this->dateTime, $connectedServiceProvider->getFirstAuthenticationAt());
        $this->assertSame($this->dateTime, $connectedServiceProvider->getLastAuthenticationAt());
        $this->assertSame($this->userStub, $connectedServiceProvider->getUser());
    }
}
