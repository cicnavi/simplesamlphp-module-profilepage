<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

class Event extends AbstractPayload
{
    protected State $state;
    protected \DateTimeImmutable $happenedAt;

    public function __construct(State $state, \DateTimeImmutable $happenedAt = null)
    {
        $this->state = $state;
        $this->happenedAt = $happenedAt ?? new \DateTimeImmutable();
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function getHappenedAt(): \DateTimeImmutable
    {
        return $this->happenedAt;
    }

    public function getRawPayloadData(): array
    {
        return $this->state->toArray();
    }
}
