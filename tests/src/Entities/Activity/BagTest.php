<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Activity;

use SimpleSAML\Module\profilepage\Entities\Activity;
use SimpleSAML\Module\profilepage\Entities\Activity\Bag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Activity\Bag
 */
class BagTest extends TestCase
{
    public function testCanAddActivity(): void
    {
        $activityStub = $this->createStub(Activity::class);
        $bag = new Bag();

        $this->assertEmpty($bag->getAll());

        $bag->add($activityStub);

        $this->assertNotEmpty($bag->getAll());
    }
}
