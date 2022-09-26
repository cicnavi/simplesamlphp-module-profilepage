<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\NetworkHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\NetworkHelper
 */
class NetworkHelperTest extends TestCase
{
    protected string $ipAddress;

    protected function setUp(): void
    {
        $this->ipAddress = '123.123.123.123';
    }

    public function testCanGetIpFromParameter(): void
    {
        $this->assertSame($this->ipAddress, NetworkHelper::resolveClientIpAddress($this->ipAddress));
    }

    public function testReturnsNullForInvalidIp(): void
    {
        $this->assertNull(NetworkHelper::resolveClientIpAddress('invalid'));
    }

    public function testReturnsNullForNonExistentIp(): void
    {
        $this->assertNull(NetworkHelper::resolveClientIpAddress());
    }

    /**
     * @backupGlobals enabled
     */
    public function testCanResolveIpAddress(): void
    {
        global $_SERVER;

        $_SERVER['REMOTE_ADDR'] = $this->ipAddress;

        $this->assertSame($this->ipAddress, NetworkHelper::resolveClientIpAddress());
    }
}
