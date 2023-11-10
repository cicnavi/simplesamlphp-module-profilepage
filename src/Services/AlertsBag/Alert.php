<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services\AlertsBag;

class Alert
{
    public function __construct(protected string $message, protected string $level)
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLevel(): string
    {
        return $this->level;
    }
}
