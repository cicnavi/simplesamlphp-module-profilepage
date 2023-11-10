<?php

namespace SimpleSAML\Test\Module\accounting\Services\Serializers;

use SimpleSAML\Module\accounting\Services\Serializers\JsonSerializer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\Serializers\JsonSerializer
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
