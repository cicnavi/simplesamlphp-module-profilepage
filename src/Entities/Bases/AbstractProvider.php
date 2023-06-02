<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected array $metadata;
    protected string $entityId;

    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
        $this->entityId = $this->resolveEntityId();
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    abstract public function getName(string $locale = 'en'): ?string;
    abstract public function getDescription(string $locale = 'en'): ?string;
    abstract protected function resolveEntityId(): string;
    abstract public function getProtocol(): AuthenticationProtocolInterface;
}
