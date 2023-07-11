<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Providers\Service;

use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider;

class Saml2 extends AbstractSaml2Provider implements ServiceProviderInterface
{
    protected function getProviderDescription(): string
    {
        return $this->getProtocol()->getDesignation() . ' Service Provider';
    }
}
