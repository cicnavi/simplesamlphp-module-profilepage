<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Providers\Identity;

use SimpleSAML\Module\profilepage\Entities\Interfaces\IdentityProviderInterface;
use SimpleSAML\Module\profilepage\Entities\Providers\Bases\AbstractSaml2Provider;

class Saml2 extends AbstractSaml2Provider implements IdentityProviderInterface
{
    protected function getProviderDescription(): string
    {
        return $this->getProtocol()->getDesignation() . ' Identity Provider';
    }
}
