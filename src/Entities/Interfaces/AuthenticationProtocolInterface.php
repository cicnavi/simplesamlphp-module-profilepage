<?php

namespace SimpleSAML\Module\accounting\Entities\Interfaces;

interface AuthenticationProtocolInterface
{
    public function getDesignation(): string;
    public function getId(): int;
}
