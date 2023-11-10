<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services\Serializers;

use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;

class JsonSerializer implements SerializerInterface
{
    /**
     * @inheritDoc
     * @throws \JsonException
     */
    public function do(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    public function undo(string $value): mixed
    {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
