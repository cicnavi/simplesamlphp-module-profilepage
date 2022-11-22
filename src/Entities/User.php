<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

class User
{
    protected array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
