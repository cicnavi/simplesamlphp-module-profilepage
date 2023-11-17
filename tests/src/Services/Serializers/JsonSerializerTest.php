<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services\Serializers;

use SimpleSAML\Module\profilepage\Services\Serializers\JsonSerializer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\Serializers\JsonSerializer
 */
class JsonSerializerTest extends TestCase
{
    protected const DATA = ['a' => 'b'];

    protected function mocked(): JsonSerializer
    {
        return new JsonSerializer();
    }

    public function testCanSerializeData(): void
    {
        $serialized = json_encode(self::DATA);

        $this->assertSame($serialized, $this->mocked()->do(self::DATA));
        $this->assertSame(self::DATA, $this->mocked()->undo($serialized));
    }
}
