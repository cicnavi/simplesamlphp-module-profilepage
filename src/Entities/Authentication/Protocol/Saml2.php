<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication\Protocol;

use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;

class Saml2 implements AuthenticationProtocolInterface
{
    final public const DESIGNATION = 'SAML2';
    final public const ID = 1;

    public function getDesignation(): string
    {
        return self::DESIGNATION;
    }

    public function getId(): int
    {
        return self::ID;
    }
}
