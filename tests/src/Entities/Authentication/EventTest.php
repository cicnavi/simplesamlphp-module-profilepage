<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Authentication;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class EventTest extends TestCase
{
    public function testCanGetState(): void
    {
        $dateTime = new DateTimeImmutable();
        $authenticationEvent = new Event(new State\Saml2(StateArrays::SAML2_FULL), $dateTime);

        $this->assertInstanceOf(State\Saml2::class, $authenticationEvent->getState());

        $this->assertSame(
            StateArrays::SAML2_FULL['Source']['entityid'],
            $authenticationEvent->getState()->getIdentityProviderEntityId()
        );

        $this->assertEquals($dateTime, $authenticationEvent->getHappenedAt());
    }
}
