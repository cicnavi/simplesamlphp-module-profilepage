<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Traits\HasUserAttributesTrait;

class User
{
    use HasUserAttributesTrait;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }
}
