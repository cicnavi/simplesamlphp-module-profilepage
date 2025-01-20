<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Oidc;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractState;
use SimpleSAML\Module\profilepage\Entities\Interfaces\StateInterface;
use SimpleSAML\Module\profilepage\Exceptions\StateException;

use function array_key_exists;

class AuthenticationEventStateResolver
{
    /**
     * @throws StateException
     */
    public function fromStateArray(array $state): StateInterface
    {
        try {
            return new Saml2($state);
        } catch (\Throwable) {
            // Not SAML2.
        }

        try {
            return new Oidc($state);
        } catch (\Throwable) {
            // Not OIDC.
        }

        throw new StateException('Can not resolve state instance for particular authentication protocol.');
    }
}
