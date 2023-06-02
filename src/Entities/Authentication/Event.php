<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Entities\Interfaces\StateInterface;

class Event extends AbstractPayload
{
    protected StateInterface $state;
    protected DateTimeImmutable $happenedAt;

    public function __construct(StateInterface $state, DateTimeImmutable $happenedAt = null)
    {
        $this->state = $state;
        $this->happenedAt = $happenedAt ?? new DateTimeImmutable();
    }

    public function getState(): StateInterface
    {
        return $this->state;
    }

    public function getHappenedAt(): DateTimeImmutable
    {
        return $this->happenedAt;
    }
}
