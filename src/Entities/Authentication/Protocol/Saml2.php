<?php

namespace SimpleSAML\Module\accounting\Entities\Authentication\Protocol;

use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;

class Saml2 implements AuthenticationProtocolInterface
{
    public const DESIGNATION = 'SAML2';
    public const ID = 1;

    public function getDesignation(): string
    {
        return self::DESIGNATION;
    }

    public function getId(): int
    {
        return self::ID;
    }
}
