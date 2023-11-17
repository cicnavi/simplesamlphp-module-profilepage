<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Providers\Service;

use SimpleSAML\Module\profilepage\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\profilepage\Entities\Providers\Bases\AbstractOidcProvider;
use SimpleSAML\Module\profilepage\Exceptions\MetadataException;

class Oidc extends AbstractOidcProvider implements ServiceProviderInterface
{
    final public const METADATA_KEY_ENTITY_ID = 'id';
    final public const METADATA_KEY_NAME = 'name';
    final public const METADATA_KEY_DESCRIPTION = 'description';

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

        throw new MetadataException($this->getProviderDescription() . ' metadata does not contain entity ID.');
    }

    protected function getProviderDescription(): string
    {
        return $this->getProtocol()->getDesignation() . ' Relying Party';
    }
}
