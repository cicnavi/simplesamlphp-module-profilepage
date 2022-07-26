<?php

namespace SimpleSAML\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;

abstract class AbstractJob implements JobInterface
{
    protected AbstractPayload $payload;

    public function __construct(AbstractPayload $payload)
    {
        $this->setPayload($payload);
    }

    public function getPayload(): AbstractPayload
    {
        return $this->payload;
    }

    public function setPayload(AbstractPayload $payload): void
    {
        $this->payload = $payload;
    }
}
