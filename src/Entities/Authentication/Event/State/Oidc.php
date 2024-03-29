<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;

use SimpleSAML\Module\profilepage\Entities\Providers\Identity;
use SimpleSAML\Module\profilepage\Entities\Providers\Service;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractState;
use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;

class Oidc extends AbstractState
{
    final public const KEY_OIDC = 'Oidc';

    final public const KEY_OPEN_ID_PROVIDER_METADATA = 'OpenIdProviderMetadata';

    final public const KEY_RELYING_PARTY_METADATA = 'RelyingPartyMetadata';

    protected function resolveIdentityProviderMetadata(array $state): array
    {
        $oidcState = $this->extractOidcPart($state);

        if (
            !empty($oidcState[self::KEY_OPEN_ID_PROVIDER_METADATA]) &&
            is_array($oidcState[self::KEY_OPEN_ID_PROVIDER_METADATA])
        ) {
            return $oidcState[self::KEY_OPEN_ID_PROVIDER_METADATA];
        }

        throw new UnexpectedValueException('State array does not contain OpenID VersionedDataProvider metadata.');
    }

    protected function resolveIdentityProviderEntityId(): string
    {
        if (
            !empty($this->identityProviderMetadata[Identity\Oidc::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->identityProviderMetadata[Identity\Oidc::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->identityProviderMetadata[Identity\Oidc::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('OpenID VersionedDataProvider metadata array does not contain issuer.');
    }

    protected function resolveServiceProviderMetadata(array $state): array
    {
        $oidcState = $this->extractOidcPart($state);

        if (
            !empty($oidcState[self::KEY_RELYING_PARTY_METADATA]) &&
            is_array($oidcState[self::KEY_RELYING_PARTY_METADATA])
        ) {
            return $oidcState[self::KEY_RELYING_PARTY_METADATA];
        }

        throw new UnexpectedValueException('State array does not contain Relying Party metadata.');
    }

    protected function resolveServiceProviderEntityId(): string
    {
        if (
            !empty($this->serviceProviderMetadata[Service\Oidc::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->serviceProviderMetadata[Service\Oidc::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->serviceProviderMetadata[Service\Oidc::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('Relying Party metadata array does not contain entity ID.');
    }

    public function getAuthenticationProtocol(): AuthenticationProtocolInterface
    {
        return new Protocol\Oidc();
    }

    protected function extractOidcPart(array $state): array
    {
        if (
            !empty($state[self::KEY_OIDC]) &&
            is_array($state[self::KEY_OIDC])
        ) {
            return $state[self::KEY_OIDC];
        }

        throw new UnexpectedValueException('State array does not contain OpenID Connect protocol metadata.');
    }
}
