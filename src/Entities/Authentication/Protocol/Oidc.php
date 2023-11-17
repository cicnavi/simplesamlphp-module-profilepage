<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication\Protocol;

use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;

class Oidc implements AuthenticationProtocolInterface
{
    final public const DESIGNATION = 'OIDC';
    final public const ID = 2;

    public function getDesignation(): string
    {
        return self::DESIGNATION;
    }

    public function getId(): int
    {
        return self::ID;
    }
}
