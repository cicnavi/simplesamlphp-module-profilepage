<?php

namespace SimpleSAML\Test\Module\accounting\Entities\ConnectedServiceProvider;

use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider\Bag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider\Bag
 */
class BagTest extends TestCase
{
    public function testCanAddConnectedService(): void
    {
        $connectedServiceProvider = $this->createStub(ConnectedServiceProvider::class);
        $bag = new Bag();

        $this->assertEmpty($bag->getAll());
        $bag->addOrReplace($connectedServiceProvider);
        $this->assertNotEmpty($bag->getAll());
    }
}
