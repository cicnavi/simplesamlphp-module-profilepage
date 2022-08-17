<?php

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\AuthenticationEvent
 */
class AuthenticationEventTest extends TestCase
{
    protected array $state = [
        'sample' => 'value',
    ];


    public function testCanGetState(): void
    {
        $authenticationEvent = new AuthenticationEvent($this->state);

        $this->assertArrayHasKey('sample', $authenticationEvent->getState());
    }
}
