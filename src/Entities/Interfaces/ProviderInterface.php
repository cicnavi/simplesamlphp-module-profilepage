<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Interfaces;

interface ProviderInterface
{
    public const DEFAULT_LOCALE = 'en';

    public function getMetadata(): array;
    public function getName(string $locale = self::DEFAULT_LOCALE): ?string;
    public function getEntityId(): string;
    public function getDescription(string $locale = self::DEFAULT_LOCALE): ?string;
    public function getLogoUrl(): ?string;
    public function getProtocol(): AuthenticationProtocolInterface;
}
