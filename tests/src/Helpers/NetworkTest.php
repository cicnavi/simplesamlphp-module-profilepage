<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Helpers;

use SimpleSAML\Module\profilepage\Helpers\Network;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Helpers\Network
 */
class NetworkTest extends TestCase
{
    protected string $ipAddress;

    protected function setUp(): void
    {
        $this->ipAddress = '123.123.123.123';
    }

    public function testCanGetIpFromParameter(): void
    {
        $this->assertSame($this->ipAddress, (new Network())->resolveClientIpAddress($this->ipAddress));
    }

    public function testReturnsNullForInvalidIp(): void
    {
        $this->assertNull((new Network())->resolveClientIpAddress('invalid'));
    }

    public function testReturnsNullForNonExistentIp(): void
    {
        $this->assertNull((new Network())->resolveClientIpAddress());
    }

    /**
     * @backupGlobals enabled
     */
    public function testCanResolveIpAddress(): void
    {
        global $_SERVER;

        $_SERVER['REMOTE_ADDR'] = $this->ipAddress;

        $this->assertSame($this->ipAddress, (new Network())->resolveClientIpAddress());
    }

    /**
     * @backupGlobals enabled
     */
    public function testReturnsNullIfIpIsNotString(): void
    {
        global $_SERVER;

        $_SERVER['REMOTE_ADDR'] = false;

        $this->assertSame(null, (new Network())->resolveClientIpAddress());
    }
}
