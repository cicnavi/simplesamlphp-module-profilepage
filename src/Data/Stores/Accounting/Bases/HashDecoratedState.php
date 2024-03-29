<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases;

use SimpleSAML\Module\profilepage\Entities\Interfaces\StateInterface;
use SimpleSAML\Module\profilepage\Services\HelpersManager;

class HashDecoratedState
{
    protected HelpersManager $helpersManager;

    protected string $identityProviderEntityIdHashSha256;
    protected string $serviceProviderEntityIdHashSha256;
    protected string $identityProviderMetadataArrayHashSha256;
    protected string $serviceProviderMetadataArrayHashSha256;
    protected string $attributesArrayHashSha256;

    public function __construct(protected StateInterface $state, HelpersManager $helpersManager = null)
    {
        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->identityProviderEntityIdHashSha256 = $this->helpersManager->getHash()
            ->getSha256($state->getIdentityProviderEntityId());
        $this->identityProviderMetadataArrayHashSha256 = $this->helpersManager->getHash()
            ->getSha256ForArray($state->getIdentityProviderMetadata());

        $this->serviceProviderEntityIdHashSha256 = $this->helpersManager->getHash()
            ->getSha256($state->getServiceProviderEntityId());
        $this->serviceProviderMetadataArrayHashSha256 = $this->helpersManager->getHash()
            ->getSha256ForArray($state->getServiceProviderMetadata());

        $this->attributesArrayHashSha256 = $this->helpersManager->getHash()
            ->getSha256ForArray($state->getAttributes());
    }

    public function getState(): StateInterface
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
