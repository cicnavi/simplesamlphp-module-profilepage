<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses \SimpleSAML\Module\accounting\Helpers\NetworkHelper
 */
class EventTest extends TestCase
{
    public function testCanGetState(): void
    {
        $dateTime = new \DateTimeImmutable();
        $authenticationEvent = new Event(new State(StateArrays::FULL), $dateTime);

        $this->assertInstanceOf(State::class, $authenticationEvent->getState());

        $this->assertSame(
            StateArrays::FULL['Source']['entityid'],
            $authenticationEvent->getState()->getIdentityProviderEntityId()
        );

        $this->assertEquals($dateTime, $authenticationEvent->getHappenedAt());
    }
}
