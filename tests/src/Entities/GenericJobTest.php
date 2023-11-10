<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities;

use SimpleSAML\Module\profilepage\Entities\GenericJob;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\GenericJob
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 */
class GenericJobTest extends TestCase
{
    public function testCanGetProperType(): void
    {
        $job = new GenericJob(StateArrays::SAML2_FULL);

        $this->assertSame(GenericJob::class, $job->getType());
    }
}
