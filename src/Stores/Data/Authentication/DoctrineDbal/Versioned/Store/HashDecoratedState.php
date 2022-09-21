<?php

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Helpers\HashHelper;

class HashDecoratedState
{
    protected State $state;
    protected string $identityProviderEntityIdHashSha256;
    protected string $serviceProviderEntityIdHashSha256;
    protected string $identityProviderMetadataArrayHashSha256;
    protected string $serviceProviderMetadataArrayHashSha256;
    protected string $attributesArrayHashSha256;

    public function __construct(State $state)
    {
        $this->state = $state;

        $this->identityProviderEntityIdHashSha256 = HashHelper::getSha256($state->getIdentityProviderEntityId());
        $this->identityProviderMetadataArrayHashSha256 =
            HashHelper::getSha256ForArray($state->getIdentityProviderMetadata());

        $this->serviceProviderEntityIdHashSha256 = HashHelper::getSha256($state->getServiceProviderEntityId());
        $this->serviceProviderMetadataArrayHashSha256 =
            HashHelper::getSha256ForArray($state->getServiceProviderMetadata());

        $this->attributesArrayHashSha256 = HashHelper::getSha256ForArray($state->getAttributes());
    }

    /**
     * @return State
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getIdentityProviderEntityIdHashSha256(): string
    {
        return $this->identityProviderEntityIdHashSha256;
    }

    /**
     * @return string
     */
    public function getServiceProviderEntityIdHashSha256(): string
    {
        return $this->serviceProviderEntityIdHashSha256;
    }

    /**
     * @return string
     */
    public function getIdentityProviderMetadataArrayHashSha256(): string
    {
        return $this->identityProviderMetadataArrayHashSha256;
    }

    public function getServiceProviderMetadataArrayHashSha256(): string
    {
        return $this->serviceProviderMetadataArrayHashSha256;
    }

    /**
     * @return string
     */
    public function getAttributesArrayHashSha256(): string
    {
        return $this->attributesArrayHashSha256;
    }
}
