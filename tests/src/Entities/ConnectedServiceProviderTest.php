<?php

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\ServiceProvider;
use SimpleSAML\Module\accounting\Entities\User;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider
 */
class ConnectedServiceProviderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ServiceProvider|ServiceProvider&\PHPUnit\Framework\MockObject\Stub
     */
    protected $serviceProviderStub;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|User|User&\PHPUnit\Framework\MockObject\Stub
     */
    protected $userStub;
    protected \DateTimeImmutable $dateTime;
    protected int $numberOfAuthentications;

    public function setUp(): void
    {
        $this->serviceProviderStub = $this->createStub(ServiceProvider::class);
        $this->userStub = $this->createStub(User::class);
        $this->dateTime = new \DateTimeImmutable();
        $this->numberOfAuthentications = 1;
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $connectedServiceProvider = new ConnectedServiceProvider(
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
