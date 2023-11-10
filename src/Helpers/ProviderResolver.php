<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

use SimpleSAML\Module\profilepage\Entities\Interfaces\IdentityProviderInterface;
use SimpleSAML\Module\profilepage\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\profilepage\Exceptions\MetadataException;
use SimpleSAML\Module\profilepage\Entities\Providers\Service;
use SimpleSAML\Module\profilepage\Entities\Providers\Identity;
use Throwable;

class ProviderResolver
{
    /**
     * @throws MetadataException
     */
    public function forIdentityFromMetadataArray(array $metadata): IdentityProviderInterface
    {
        try {
            return new Identity\Saml2($metadata);
        } catch (Throwable) {
            // This is not SAML2...
        }

        try {
            return new Identity\Oidc($metadata);
        } catch (Throwable) {
            // This is not OIDC...
        }

        throw new MetadataException('Can not resolve identity provider form provided metadata array.');
    }

    /**
     * @throws MetadataException
     */
    public function forServiceFromMetadataArray(array $metadata): ServiceProviderInterface
    {
        try {
            return new Service\Saml2($metadata);
        } catch (Throwable) {
            // This is not SAML2...
        }

        try {
            return new Service\Oidc($metadata);
        } catch (Throwable) {
            // This is not OIDC...
        }

        throw new MetadataException('Can not resolve service provider form provided metadata array.');
    }
}
