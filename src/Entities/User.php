<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities;

use SimpleSAML\Module\profilepage\Traits\HasUserAttributesTrait;

class User
{
    use HasUserAttributesTrait;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }
}
