<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Traits;

trait HasUserAttributesTrait
{
    protected array $attributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getFirstAttributeValue(string $attributeName): ?string
    {
        if (($value = $this->getAttributeValue($attributeName)) !== null) {
            return (string)reset($value);
        }

        return null;
    }

    public function getAttributeValue(string $attributeName): ?array
    {
        if (!empty($this->attributes[$attributeName]) && is_array($this->attributes[$attributeName])) {
            return $this->attributes[$attributeName];
        }

        return null;
    }
}
