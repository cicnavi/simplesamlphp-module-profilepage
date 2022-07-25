<?php

namespace SimpleSAML\Module\accounting\Entities;

class AuthenticationEvent
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
