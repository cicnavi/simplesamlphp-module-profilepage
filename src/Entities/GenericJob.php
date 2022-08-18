<?php

namespace SimpleSAML\Module\accounting\Entities;

class GenericJob extends Bases\AbstractJob
{
    public function getType(): string
    {
        return self::class;
    }
}
