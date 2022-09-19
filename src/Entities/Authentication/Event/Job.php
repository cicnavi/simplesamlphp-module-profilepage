<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication\Event;

use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractJob;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

class Job extends AbstractJob
{
    public function getPayload(): Event
    {
        return $this->validatePayload($this->payload);
    }

    public function setPayload(AbstractPayload $payload): void
    {
        $this->payload = $this->validatePayload($payload);
    }

    protected function validatePayload(AbstractPayload $payload): Event
    {
        if (! ($payload instanceof Event)) {
            throw new UnexpectedValueException('Event Job payload must be of type Event.');
        }

        return $payload;
    }

    public function getType(): string
    {
        return self::class;
    }
}
