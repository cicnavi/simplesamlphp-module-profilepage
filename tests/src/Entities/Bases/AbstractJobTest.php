<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Entities\AuthenticationEvent\Job;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Entities\AuthenticationEvent\Job
 * @uses \SimpleSAML\Module\accounting\Entities\AuthenticationEvent
 */
class AbstractJobTest extends TestCase
{
    protected AbstractPayload $payload;

    protected function setUp(): void
    {
        $this->payload = new class extends AbstractPayload {
        };
    }

    public function testCanSetAndGetPayload(): void
    {
        $job = new class ($this->payload) extends AbstractJob  {
            public function run(): void
            {
            }
        };

        $this->assertInstanceOf(AbstractPayload::class, $job->getPayload());
    }
}
