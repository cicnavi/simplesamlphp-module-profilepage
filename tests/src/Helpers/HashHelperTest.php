<?php

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\HashHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\HashHelper
 * @uses \SimpleSAML\Module\accounting\Helpers\ArrayHelper
 */
class HashHelperTest extends TestCase
{
    protected string $data;
    protected string $dataSha256;
    /**
     * @var \int[][]
     */
    protected array $unsortedArrayData;
    protected string $unsortedArraySha256;
    /**
     * @var \int[][]
     */
    protected array $sortedArrayData;
    protected string $sortedArrayDataSha256;

    protected function setUp(): void
    {
        $this->data = 'test';
        $this->dataSha256 = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';
        $this->unsortedArrayData = ['b' => [1 => 1, 0 => 0,], 'a' => [1 => 1, 0 => 0,],];
        $this->unsortedArraySha256 = 'c467191ddddcaa5a6cc3bac28c1fd0557eb9390dbb079195a2ae70c49ce62da7';
        $this->sortedArrayData = ['a' => [0 => 0, 1 => 1,], 'b' => [0 => 0, 1 => 1,],];
        $this->sortedArrayDataSha256 = 'c467191ddddcaa5a6cc3bac28c1fd0557eb9390dbb079195a2ae70c49ce62da7';
    }

    public function testCanGetSha256ForString(): void
    {
        $this->assertSame($this->dataSha256, HashHelper::getSha256($this->data));
    }

    public function testCanGetSha256ForArray(): void
    {
        // Arrays are sorted before the hash is calculated, so the value must be the same.
        $this->assertSame($this->unsortedArraySha256, $this->sortedArrayDataSha256);
        $this->assertSame($this->unsortedArraySha256, HashHelper::getSha256ForArray($this->unsortedArrayData));
        $this->assertSame($this->sortedArrayDataSha256, HashHelper::getSha256ForArray($this->sortedArrayData));
    }
}
