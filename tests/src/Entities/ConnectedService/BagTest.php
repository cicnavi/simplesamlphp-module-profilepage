<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\ConnectedService;

use SimpleSAML\Module\profilepage\Entities\ConnectedService;
use SimpleSAML\Module\profilepage\Entities\ConnectedService\Bag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\ConnectedService\Bag
 */
class BagTest extends TestCase
{
    public function testCanAddConnectedService(): void
    {
        $connectedServiceProvider = $this->createStub(ConnectedService::class);
        $bag = new Bag();

        $this->assertEmpty($bag->getAll());
        $bag->addOrReplace($connectedServiceProvider);
        $this->assertNotEmpty($bag->getAll());
    }
}
