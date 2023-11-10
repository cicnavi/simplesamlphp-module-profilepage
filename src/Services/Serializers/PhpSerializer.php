<?php

namespace SimpleSAML\Module\accounting\Services\Serializers;

use SimpleSAML\Module\accounting\Interfaces\SerializerInterface;

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
