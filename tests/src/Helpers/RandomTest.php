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
        $this->assertIsInt((new Random())->getInt());
    }

    public function testCanGetRandomString(): void
    {
        $this->assertIsString((new Random())->getString());

        $this->assertSame(5, mb_strlen((new Random())->getString(5)));
    }
}
