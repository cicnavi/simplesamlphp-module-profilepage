<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Entities\Interfaces\IdentityProviderInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;
use SimpleSAML\Module\accounting\Entities\Providers\Service;
use SimpleSAML\Module\accounting\Entities\Providers\Identity;
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
