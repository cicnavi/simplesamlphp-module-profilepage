<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Bases;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 */
class AbstractJobTest extends TestCase
{
    protected array $rawState = [];


    public function testCanInitializeProperties(): void
    {
        $id = 1;
        $createdAt = new DateTimeImmutable();
        $job = new class ($this->rawState, $id, $createdAt) extends AbstractJob  {
            public function getType(): string
            {
                return self::class;
            }
        };

        $this->assertSame($id, $job->getId());
        $this->assertSame($createdAt, $job->getCreatedAt());
        $this->assertSame($job::class, $job->getType());
    }
}
