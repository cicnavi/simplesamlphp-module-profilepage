<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities;

class GenericJob extends Bases\AbstractJob
{
    public function getType(): string
    {
        return self::class;
    }
}
