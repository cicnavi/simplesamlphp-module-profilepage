<?php

namespace SimpleSAML\Test\Module\accounting\Services\AlertsBag;

use SimpleSAML\Module\accounting\Services\AlertsBag\Alert;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\AlertsBag\Alert
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
