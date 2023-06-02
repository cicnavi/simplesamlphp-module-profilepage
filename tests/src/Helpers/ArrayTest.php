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

    public function testCanGroupByValue(): void
    {
        $ungrouped = [
            ['a' => '1', 'b' => '1'],
            ['a' => '1', 'b' => '2'],
            ['a' => '2', 'b' => '3'],
            ['a' => '2', 'b' => '4'],
            ['a' => '3', 'b' => '5'],
        ];

        $expected = [
            '1' => [
                ['a' => '1', 'b' => '1'],
                ['a' => '1', 'b' => '2'],
            ],
            '2' => [
                ['a' => '2', 'b' => '3'],
                ['a' => '2', 'b' => '4'],
            ],
            '3' => [
                ['a' => '3', 'b' => '5'],
            ],
        ];

        $this->assertNotSame($ungrouped, $expected);
        $grouped = (new Arr())->groupByValue($ungrouped, 'a');
        $this->assertSame($grouped, $expected);
    }

    public function testIsAssociative(): void
    {
        $nonAssociative = ['a', 'b', 'c'];
        $associative = ['a' => 1, 'b' => 2, 'c' => 3];
        $mixed = ['a', 'b' => 2, 'c'];

        $this->assertFalse((new Arr())->isAssociative($nonAssociative));
        $this->assertTrue((new Arr())->isAssociative($associative));
        $this->assertTrue((new Arr())->isAssociative($mixed));
    }
}
