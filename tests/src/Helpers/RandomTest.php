<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\Random;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\Random
 */
class RandomTest extends TestCase
{
    public function testCanGetRandomInt(): void
    {
        $this->assertIsInt((new Random())->getRandomInt());
    }
}
