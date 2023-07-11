<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Providers\Identity;

use SimpleSAML\Module\accounting\Entities\Authentication;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\IdentityProviderInterface;
use SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;

class Saml2 extends AbstractSaml2Provider implements IdentityProviderInterface
{
    protected function getProviderDescription(): string
    {
        return $this->getProtocol()->getDesignation() . ' Identity Provider';
    }
}
