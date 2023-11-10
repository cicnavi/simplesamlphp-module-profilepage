<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services\AlertsBag;

use SimpleSAML\Module\profilepage\Services\AlertsBag\Alert;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\AlertsBag\Alert
 */
class AlertTest extends TestCase
{
    public function testCanInstantiateAlert(): void
    {
        $alert = new Alert('message', 'level');
        $this->assertSame('message', $alert->getMessage());
        $this->assertSame('level', $alert->getLevel());
    }
}
