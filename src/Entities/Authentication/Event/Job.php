<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication\Event;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;

class Job extends AbstractJob
{
    public function getType(): string
    {
        return self::class;
    }
}
