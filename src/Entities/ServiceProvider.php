<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

class ServiceProvider
{
    protected array $metadata;

    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getMetadataArray(): array
    {
        return $this->metadata;
    }

    public function getName(string $locale = 'en'): ?string
    {
        if (!empty($this->metadata['name'][$locale]) && is_string($this->metadata['name'][$locale])) {
            return $this->metadata['name'][$locale];
        }

        return null;
    }

    public function getEntityId(): string
    {
        if (!empty($this->metadata['entityid']) && is_string($this->metadata['entityid'])) {
            return $this->metadata['entityid'];
        }

        throw new UnexpectedValueException('Service provider metadata does not contain entity ID.');
    }
}
