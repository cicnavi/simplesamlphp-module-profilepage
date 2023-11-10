<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication\Event;

use SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob;

class Job extends AbstractJob
{
    public function getType(): string
    {
        return self::class;
    }
}
