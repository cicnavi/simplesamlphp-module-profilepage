<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

class GenericJob extends Bases\AbstractJob
{
    public function getType(): string
    {
        return self::class;
    }
}
