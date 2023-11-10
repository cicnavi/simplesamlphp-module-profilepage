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
        if (
            ! array_key_exists(AbstractState::KEY_ATTRIBUTES, $state) ||
            ! is_array($state[AbstractState::KEY_ATTRIBUTES])
        ) {
            throw new StateException('State array does not contain user attributes.');
        }

        if (
            array_key_exists(Saml2::KEY_IDENTITY_PROVIDER_METADATA, $state) &&
            is_array($state[Saml2::KEY_IDENTITY_PROVIDER_METADATA]) &&
            array_key_exists(Saml2::KEY_SERVICE_PROVIDER_METADATA, $state) &&
            is_array($state[Saml2::KEY_SERVICE_PROVIDER_METADATA])
        ) {
            // Authentication was done using SAML2 protocol...
            return new Saml2($state);
        }

        $oidcRelatedState = (array)($state[Oidc::KEY_OIDC] ?? []);

        if (
            array_key_exists(Oidc::KEY_OPEN_ID_PROVIDER_METADATA, $oidcRelatedState) &&
            is_array($oidcRelatedState[Oidc::KEY_OPEN_ID_PROVIDER_METADATA]) &&
            array_key_exists(Oidc::KEY_RELYING_PARTY_METADATA, $oidcRelatedState) &&
            is_array($oidcRelatedState[Oidc::KEY_RELYING_PARTY_METADATA])
        ) {
            // Authentication was done using OIDC protocol...
            return new Oidc($state);
        }

        throw new StateException('Can not resolve state instance for particular authentication protocol.');
    }
}
