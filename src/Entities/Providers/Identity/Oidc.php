<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Providers\Identity;

use SimpleSAML\Module\accounting\Entities\Interfaces\IdentityProviderInterface;
use SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractOidcProvider;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;

class Oidc extends AbstractOidcProvider implements IdentityProviderInterface
{
    public const METADATA_KEY_ENTITY_ID = 'issuer';

    public function getName(string $locale = 'en'): ?string
    {
        return null;
    }

    public function getDescription(string $locale = 'en'): ?string
    {
        return null;
    }

    /**
     * @throws MetadataException
     */
    protected function resolveEntityId(): string
    {
        if (
            !empty($this->metadata[self::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->metadata[self::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->metadata[self::METADATA_KEY_ENTITY_ID];
        }

        throw new MetadataException($this->getProviderDescription() . ' metadata does not contain entity ID.');
    }

    protected function getProviderDescription(): string
    {
        return $this->getProtocol()->getDesignation() . ' OpenID Provider';
    }
}
