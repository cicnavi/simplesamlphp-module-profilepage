<?php

namespace SimpleSAML\Module\profilepage\Services\Serializers;

use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;

class PhpSerializer implements SerializerInterface
{
    /**
     * @inheritDoc
     */
    public function do(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * @inheritDoc
     */
    public function undo(string $value): mixed
    {
        return unserialize($value);
    }
}
