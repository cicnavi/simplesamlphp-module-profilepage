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

    public function testCanInitializeProperties(): void
    {
        $id = 1;
        $createdAt = new \DateTimeImmutable();
        $job = new class ($this->payload, $id, $createdAt) extends AbstractJob  {
            public function getType(): string
            {
                return self::class;
            }
        };

        $this->assertSame($id, $job->getId());
        $this->assertSame($createdAt, $job->getCreatedAt());
        $this->assertSame(get_class($job), $job->getType());
        $this->assertInstanceOf(AbstractPayload::class, $job->getPayload());
    }
}
