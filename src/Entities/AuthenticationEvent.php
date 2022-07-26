<?php

namespace SimpleSAML\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

class AuthenticationEvent extends AbstractPayload
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
