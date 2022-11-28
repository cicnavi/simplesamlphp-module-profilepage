<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\RandomHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\RandomHelper
 */
class RandomHelperTest extends TestCase
{
    public function testCanGetRandomInt(): void
    {
        $this->assertIsInt((new RandomHelper())->getRandomInt());
    }
}
