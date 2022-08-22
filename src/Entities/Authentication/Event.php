<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

class Event extends AbstractPayload
{
    protected array $state;

    public function __construct(array $state)
    {
        $this->state = $state;
    }

    public function getState(): array
    {
        return $this->state;
    }
}
