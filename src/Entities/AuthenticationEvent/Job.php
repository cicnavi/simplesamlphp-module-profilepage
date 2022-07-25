<?php

namespace SimpleSAML\Module\accounting\Entities\AuthenticationEvent;

use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;

class Job implements JobInterface
{
    protected AuthenticationEvent $authenticationEvent;

    public function __construct(AuthenticationEvent $authenticationEvent)
    {
        $this->authenticationEvent = $authenticationEvent;
    }

    public function run(): void
    {
        // TODO: Implement run() method.
    }
}
