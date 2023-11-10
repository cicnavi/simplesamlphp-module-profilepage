<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication;

use DateTimeImmutable;
use SimpleSAML\Module\profilepage\Entities\Interfaces\StateInterface;

class Event
{
    protected DateTimeImmutable $happenedAt;

    public function __construct(protected StateInterface $state, DateTimeImmutable $happenedAt = null)
    {
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
