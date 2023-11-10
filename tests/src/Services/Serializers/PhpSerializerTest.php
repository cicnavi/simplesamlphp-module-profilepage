<?php

namespace SimpleSAML\Test\Module\accounting\Services\Serializers;

use SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer
 */
class PhpSerializerTest extends TestCase
{
    protected const DATA = ['a' => 'b'];
    protected function mocked(): PhpSerializer
    {
        return new PhpSerializer();
    }
    public function testCanSerializeDate(): void
    {
        $serialized = serialize(self::DATA);

        $this->assertSame($serialized, $this->mocked()->do(self::DATA));

        $this->assertSame(self::DATA, $this->mocked()->undo($serialized));
    }
}
