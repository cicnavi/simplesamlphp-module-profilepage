<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Event
 */
class EventTest extends TestCase
{
    protected array $state = [
        'sample' => 'value',
    ];


    public function testCanGetState(): void
    {
        $authenticationEvent = new Event($this->state);

        $this->assertArrayHasKey('sample', $authenticationEvent->getState());
    }
}
