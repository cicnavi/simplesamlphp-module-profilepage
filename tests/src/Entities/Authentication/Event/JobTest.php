<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Authentication\Event;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\Job;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
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
