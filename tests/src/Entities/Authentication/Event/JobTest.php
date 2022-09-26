<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication\Event;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses \SimpleSAML\Module\accounting\Helpers\NetworkHelper
 */
class JobTest extends TestCase
{
    public function testCanCreateInstanceWithAuthenticationEventEntity(): void
    {
        $job = new Job(new Event(new State(StateArrays::FULL)));

        $this->assertInstanceOf(Event::class, $job->getPayload());
    }

    public function testPayloadMustBeAuthenticationEventOrThrow(): void
    {
        $payload = new class extends AbstractPayload {
        };

        $this->expectException(UnexpectedValueException::class);

        (new Job($payload));
    }

    public function testCanGetProperType(): void
    {
        $job = new Job(new Event(new State(StateArrays::FULL)));

        $this->assertSame(Job::class, $job->getType());
    }
}
