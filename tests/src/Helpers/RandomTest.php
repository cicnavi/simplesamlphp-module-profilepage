<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use Exception;
use SimpleSAML\Module\accounting\Helpers\Random;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\Random
 */
class RandomTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCanGetRandomInt(): void
    {
        $this->assertIsInt((new Random())->getInt());
    }

    /**
     * @throws Exception
     */
    public function testCanGetRandomString(): void
    {
        $this->assertIsString((new Random())->getString());

        $this->assertSame(5, mb_strlen((new Random())->getString(5)));
    }
}
