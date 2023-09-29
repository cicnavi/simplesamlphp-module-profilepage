<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication\Event;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\accounting\Helpers\Network
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 */
class JobTest extends TestCase
{
    public function testCanCreateInstanceWithAuthenticationEventEntity(): void
    {
        $job = new Job(StateArrays::SAML2_FULL);

        $this->assertIsArray($job->getRawState());
    }

    public function testCanGetProperType(): void
    {
        $job = new Job(StateArrays::SAML2_FULL);

        $this->assertSame(Job::class, $job->getType());
    }
}
