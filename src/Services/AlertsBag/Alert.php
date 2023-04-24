<?php

namespace SimpleSAML\Module\accounting\Services\AlertsBag;

class Alert
{
    protected string $message;
    protected string $level;

    public function __construct(
        string $message,
        string $level
    ) {
        $this->message = $message;
        $this->level = $level;
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
