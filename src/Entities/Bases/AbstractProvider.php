<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Bases;

use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\profilepage\Entities\Interfaces\ProviderInterface;
use SimpleSAML\Module\profilepage\Services\HelpersManager;

abstract class AbstractProvider implements ProviderInterface
{
    protected HelpersManager $helpersManager;
    protected string $entityId;

    public function __construct(protected array $metadata, HelpersManager $helpersManager = null)
    {
        $this->helpersManager = $helpersManager ?? new HelpersManager();
        $this->entityId = $this->resolveEntityId();
    }

    protected function resolveOptionallyLocalizedString(
        string $key,
        string $locale = self::DEFAULT_LOCALE,
        array $metadataOverride = null
    ): ?string {
        $metadata = $metadataOverride ?? $this->metadata;

        if (!isset($metadata[$key])) {
            return null;
        }

        // Check for non-localized version.
        if (is_string($metadata[$key])) {
            return $metadata[$key];
        }

        if (
            is_array($metadata[$key]) &&
            !empty($metadata[$key][$locale]) &&
            is_string($metadata[$key][$locale])
        ) {
            return $metadata[$key][$locale];
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

    abstract public function getName(string $locale = self::DEFAULT_LOCALE): ?string;
    abstract public function getDescription(string $locale = self::DEFAULT_LOCALE): ?string;
    abstract public function getLogoUrl(): ?string;
    abstract protected function resolveEntityId(): string;
    abstract public function getProtocol(): AuthenticationProtocolInterface;
    abstract protected function getProviderDescription(): string;
}
