<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;

use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractState;
use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\profilepage\Entities\Providers\Bases\AbstractSaml2Provider;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;

class Saml2 extends AbstractState
{
    public const KEY_IDENTITY_PROVIDER_METADATA = 'IdPMetadata';
    public const KEY_SOURCE = 'Source';
    public const KEY_SERVICE_PROVIDER_METADATA = 'SPMetadata';
    public const KEY_DESTINATION = 'Destination';

    protected function resolveIdentityProviderMetadata(array $state): array
    {
        if (
            !empty($state[self::KEY_IDENTITY_PROVIDER_METADATA]) &&
            is_array($state[self::KEY_IDENTITY_PROVIDER_METADATA])
        ) {
            return $state[self::KEY_IDENTITY_PROVIDER_METADATA];
        } elseif (!empty($state[self::KEY_SOURCE]) && is_array($state[self::KEY_SOURCE])) {
            return $state[self::KEY_SOURCE];
        }

        throw new UnexpectedValueException('State array does not contain IdP metadata.');
    }

    protected function resolveServiceProviderMetadata(array $state): array
    {
        if (
            !empty($state[self::KEY_SERVICE_PROVIDER_METADATA]) &&
            is_array($state[self::KEY_SERVICE_PROVIDER_METADATA])
        ) {
            return $state[self::KEY_SERVICE_PROVIDER_METADATA];
        } elseif (!empty($state[self::KEY_DESTINATION]) && is_array($state[self::KEY_DESTINATION])) {
            return $state[self::KEY_DESTINATION];
        }

        throw new UnexpectedValueException('State array does not contain SP metadata.');
    }

    protected function resolveIdentityProviderEntityId(): string
    {
        if (
            !empty($this->identityProviderMetadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->identityProviderMetadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->identityProviderMetadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('IdP metadata array does not contain entity ID.');
    }

    protected function resolveServiceProviderEntityId(): string
    {
        if (
            !empty($this->serviceProviderMetadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->serviceProviderMetadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->serviceProviderMetadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('Service provider metadata array does not contain entity ID.');
    }

    public function getAuthenticationProtocol(): AuthenticationProtocolInterface
    {
        return new Protocol\Saml2();
    }
}
