<?php

namespace SimpleSAML\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

abstract class AbstractProvider
{
    public const METADATA_KEY_NAME = 'name';
    public const METADATA_KEY_ENTITY_ID = 'entityid';

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
        if (
            !empty($this->metadata[self::METADATA_KEY_NAME][$locale]) &&
            is_string($this->metadata[self::METADATA_KEY_NAME][$locale])
        ) {
            return $this->metadata[self::METADATA_KEY_NAME][$locale];
        }

        return null;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
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
}
