<?php

namespace SimpleSAML\Test\Module\accounting\Entities\AuthenticationEvent;

use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\AuthenticationEvent\Job;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\AuthenticationEvent\Job
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Entities\AuthenticationEvent
 */
class JobTest extends TestCase
{
    public function testCanCreateInstanceWithAuthenticationEventEntity(): void
    {
        $job = new Job(new AuthenticationEvent(['sample' => 'state']));

        $this->assertInstanceOf(AuthenticationEvent::class, $job->getPayload());
    }

    public function testPayloadMustBeAuthenticationEventOrThrow(): void
    {
        $payload = new class extends AbstractPayload {
        };

        $this->expectException(UnexpectedValueException::class);

        (new Job($payload));
    }
}
