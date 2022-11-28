<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

abstract class AbstractProvider
{
    public const METADATA_KEY_NAME = 'name';
    public const METADATA_KEY_ENTITY_ID = 'entityid';
    public const METADATA_KEY_DESCRIPTION = 'description';

    protected array $metadata;
    protected string $entityId;

    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
        $this->entityId = $this->resolveEntityId();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getName(string $locale = 'en'): ?string
    {
        return $this->resolveOptionallyLocalizedString(self::METADATA_KEY_NAME, $locale);
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getDescription(string $locale = 'en'): ?string
    {
        return $this->resolveOptionallyLocalizedString(self::METADATA_KEY_DESCRIPTION, $locale);
    }


    protected function resolveEntityId(): string
    {
        if (
            !empty($this->metadata[self::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->metadata[self::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->metadata[self::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('Provider entity metadata does not contain entity ID.');
    }

    protected function resolveOptionallyLocalizedString(string $key, string $locale = 'en'): ?string
    {
        if (!isset($this->metadata[$key])) {
            return null;
        }

        // Check for non-localized version.
        if (is_string($this->metadata[$key])) {
            return $this->metadata[$key];
        }

        if (
            is_array($this->metadata[$key]) &&
            !empty($this->metadata[$key][$locale]) &&
            is_string($this->metadata[$key][$locale])
        ) {
            return $this->metadata[$key][$locale];
        }

        return null;
    }
}
