<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Activity;

use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\Activity\Bag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Activity\Bag
 */
class BagTest extends TestCase
{
    public function testCanAddActivity(): void
    {
        $activityStub = $this->createStub(Activity::class);
        $bag = new Bag();

        $this->assertEmpty($bag->getAll());

        /** @psalm-suppress InvalidArgument */
        $bag->add($activityStub);

        $this->assertNotEmpty($bag->getAll());
    }
}
