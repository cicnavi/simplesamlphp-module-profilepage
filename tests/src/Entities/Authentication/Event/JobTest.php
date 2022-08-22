<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication\Event;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 */
class JobTest extends TestCase
{
    public function testCanCreateInstanceWithAuthenticationEventEntity(): void
    {
        $job = new Job(new Event(['sample' => 'state']));

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
        $job = new Job(new Event(['sample' => 'state']));

        $this->assertSame(Job::class, $job->getType());
    }
}
