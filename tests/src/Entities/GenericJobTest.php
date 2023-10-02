<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\GenericJob;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\GenericJob
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 */
class GenericJobTest extends TestCase
{
    public function testCanGetProperType(): void
    {
        $job = new GenericJob(StateArrays::SAML2_FULL);

        $this->assertSame(GenericJob::class, $job->getType());
    }
}
