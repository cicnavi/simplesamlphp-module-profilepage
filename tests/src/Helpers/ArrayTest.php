<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\Arr;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\Arr
 */
class ArrayTest extends TestCase
{
    public function testCanRecursivelySortByKey(): void
    {
        $unsorted = [
            'b' => [1 => 1, 0 => 0],
            'a' => [1 => 1, 0 => 0],
        ];

        $sorted = [
            'a' => [0 => 0, 1 => 1],
            'b' => [0 => 0, 1 => 1],
        ];

        $this->assertNotSame($unsorted, $sorted);

        (new Arr())->recursivelySortByKey($unsorted);

        $this->assertSame($unsorted, $sorted);
    }
}
