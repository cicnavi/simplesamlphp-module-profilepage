<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Providers\Service;

use SimpleSAML\Module\accounting\Entities\Authentication;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;

class Saml2 extends AbstractProvider implements ServiceProviderInterface
{
    public const METADATA_KEY_ENTITY_ID = 'entityid';
    public const METADATA_KEY_NAME = 'name';
    public const METADATA_KEY_DESCRIPTION = 'description';

    public function getName(string $locale = 'en'): ?string
    {
        return $this->resolveOptionallyLocalizedString(self::METADATA_KEY_NAME, $locale);
    }

    public function getDescription(string $locale = 'en'): ?string
    {
        return $this->resolveOptionallyLocalizedString(self::METADATA_KEY_DESCRIPTION, $locale);
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

        throw new MetadataException('Service provider metadata does not contain entity ID.');
    }

    public function getProtocol(): AuthenticationProtocolInterface
    {
        return new Authentication\Protocol\Saml2();
    }
}
