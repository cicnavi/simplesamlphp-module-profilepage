<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Providers\Bases;

use SimpleSAML\Module\profilepage\Entities\Bases\AbstractProvider;
use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\profilepage\Exceptions\MetadataException;
use SimpleSAML\Module\profilepage\Entities\Authentication;

abstract class AbstractSaml2Provider extends AbstractProvider
{
    public const METADATA_KEY_ENTITY_ID = 'entityid';
    public const METADATA_KEY_NAME = 'name';
    public const METADATA_KEY_DESCRIPTION = 'description';
    public const METADATA_KEY_UI_INFO = 'UIInfo';
    public const METADATA_KEY_UI_INFO_DESCRIPTION = 'Description';
    public const METADATA_KEY_UI_INFO_DISPLAY_NAME = 'DisplayName';
    public const METADATA_KEY_UI_INFO_LOGO = 'Logo';
    public const METADATA_KEY_UI_INFO_LOGO_URL = 'url';

    protected function getEntityInfoString(string $key, string $locale = self::DEFAULT_LOCALE): ?string
    {
        return $this->resolveOptionallyLocalizedString($key, $locale);
    }

    protected function getEntityUiInfoString(string $key, string $locale = self::DEFAULT_LOCALE): ?string
    {
        if (
            isset($this->metadata[self::METADATA_KEY_UI_INFO]) &&
            is_array($this->metadata[self::METADATA_KEY_UI_INFO])
        ) {
            return $this->resolveOptionallyLocalizedString(
                $key,
                $locale,
                $this->metadata[self::METADATA_KEY_UI_INFO]
            );
        }

        return null;
    }

    public function getName(string $locale = self::DEFAULT_LOCALE): ?string
    {
        return $this->getEntityInfoString(self::METADATA_KEY_NAME, $locale) ??
            $this->getEntityUiInfoString(self::METADATA_KEY_UI_INFO_DISPLAY_NAME, $locale);
    }

    public function getDescription(string $locale = self::DEFAULT_LOCALE): ?string
    {
        return $this->getEntityInfoString(self::METADATA_KEY_DESCRIPTION, $locale) ??
            $this->getEntityUiInfoString(self::METADATA_KEY_UI_INFO_DESCRIPTION, $locale);
    }

    public function getLogoUrl(): ?string
    {
        $logoElement = $this->helpersManager->getArr()->getNestedElementByKey(
            $this->metadata,
            self::METADATA_KEY_UI_INFO,
            self::METADATA_KEY_UI_INFO_LOGO,
            0,
            self::METADATA_KEY_UI_INFO_LOGO_URL
        );

        if (!is_array($logoElement)) {
            return null;
        }

        /** @var mixed $logoUrl */
        $logoUrl = current($logoElement);

        if (is_string($logoUrl) && filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            return $logoUrl;
        }

        return null;
    }

    /**
     * @throws MetadataException
     */
    protected function resolveEntityId(): string
    {
        if (empty($entityId = $this->getEntityInfoString(self::METADATA_KEY_ENTITY_ID))) {
            $message = sprintf('Provider metadata does not contain entity ID (%s).', $this->getProviderDescription());
            throw new MetadataException($message);
        }

        return $entityId;
    }

    public function getProtocol(): AuthenticationProtocolInterface
    {
        return new Authentication\Protocol\Saml2();
    }
}
