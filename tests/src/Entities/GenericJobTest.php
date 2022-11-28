<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\GenericJob
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 */
class GenericJobTest extends TestCase
{
    public function testCanGetProperType(): void
    {
        $job = new GenericJob($this->createStub(AbstractPayload::class));

        $this->assertSame(GenericJob::class, $job->getType());
    }
}
