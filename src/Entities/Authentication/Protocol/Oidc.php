<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication\Protocol;

use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;

class Oidc implements AuthenticationProtocolInterface
{
    public const DESIGNATION = 'OIDC';
    public const ID = 2;

    public function getDesignation(): string
    {
        return self::DESIGNATION;
    }

    public function getId(): int
    {
        return self::ID;
    }
}
